<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Servicios\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\AttachedFileRelation;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CopyModel
{
    public function before(): Closure
    {
        return function($model) {
            if ($this->modelClass === 'ServicioAT') {
                $this->title = Tools::lang()->trans('copy') . ' ' . Tools::lang()->trans('service') . ' ' . $model->primaryDescription();
                $this->setTemplate('CopyServicioAT');
            }
        };
    }

    public function saveAction(): Closure
    {
        return function ($model, CodeModel $codeModel) {
            if ($this->modelClass === 'ServicioAT') {
                $this->saveServicioAT();
            }
        };
    }

    protected function saveServicioAT(): Closure
    {
        return function () {
            if (false === $this->validateFormToken()) {
                return;
            }

            $this->dataBase->beginTransaction();

            // obtenemos el servicio de origen
            /** @var ServicioAT $serviceOrigen */
            $serviceOrigen = $this->model;

            // obtenemos los trabajos del servicio origen
            $worksServiceOrigen = $serviceOrigen->getTrabajos();

            // creamos el nuevo producto y copiamos los campos del producto origen
            $serviceDestiny = new ServicioAT();

            $fieldsService = array_keys((new ServicioAT())->getModelFields());
            $fieldsServiceExclude = ['codigo', 'editable', 'fecha', 'hora', 'idestado', 'idservicio', 'description'];

            foreach ($fieldsService as $campo) {
                if (false === in_array($campo, $fieldsServiceExclude)) {
                    $serviceDestiny->{$campo} = $serviceOrigen->{$campo};
                }
            }

            $serviceDestiny->fecha = $this->request->request->get('fecha');
            $serviceDestiny->hora = $this->request->request->get('hora');
            $serviceDestiny->descripcion = $this->request->request->get('descripcion');

            if (false === $serviceDestiny->save()) {
                Tools::log()->warning('record-save-error');
                $this->dataBase->rollback();
                return;
            }

            // creamos los nuevos trabajos
            $fieldsWork = array_keys((new TrabajoAT())->getModelFields());
            $fieldsWorkExclude = ['estado', 'idservicio', 'idtrabajo', 'fechainicio', 'horainicio'];

            $startDate = $this->request->request->getArray('fechainicio', false);
            $startHour = $this->request->request->getArray('horainicio', false);
            foreach ($worksServiceOrigen as $index => $work) {
                $workDestiny = new TrabajoAT();

                foreach ($fieldsWork as $campo) {
                    if (false === in_array($campo, $fieldsWorkExclude)) {
                        $workDestiny->{$campo} = $work->{$campo};
                    }
                }

                // asignamos trabajos al servicio nuevo
                $workDestiny->idservicio = $serviceDestiny->idservicio;

                if (isset($startDate[$index])) {
                    $workDestiny->fechainicio = $startDate[$index];
                }

                if (isset($startHour[$index])) {
                    $workDestiny->horainicio = $startHour[$index];
                }

                if (false === $workDestiny->save()) {
                    Tools::log()->warning('record-save-error');
                    $this->dataBase->rollback();
                    return;
                }
            }

            if ((bool)$this->request->request->get('copy-attachments', false)) {
                $where = [
                    new DataBaseWhere('model', $this->modelClass),
                    new DataBaseWhere('modelid|modelcode', $serviceOrigen->idservicio),
                ];
                foreach (AttachedFileRelation::all($where, [], 0, 0) as $file) {
                    $newRelation = new AttachedFileRelation();
                    $newRelation->model = $this->modelClass;
                    $newRelation->modelid = $serviceDestiny->idservicio;
                    $newRelation->modelcode = $serviceDestiny->idservicio;
                    $newRelation->idfile = $file->idfile;
                    $newRelation->nick = $file->nick;
                    $newRelation->observations = $file->observations;

                    if (false === $newRelation->save()) {
                        Tools::log()->warning('record-save-error');
                        $this->dataBase->rollback();
                        return;
                    }
                }
            }

            $this->dataBase->commit();
            Tools::log()->notice('record-updated-correctly');
            $this->redirect($serviceDestiny->url() . '&action=save-ok');
        };
    }
}
