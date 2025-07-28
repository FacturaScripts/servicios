<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Lib\Export;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Plugins\PlantillasPDF\Lib\Export\PDFExport;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class PlantillasPDFserviciosExport extends PDFExport
{
    /**
     * @param ServicioAT $model
     * @param array $columns
     * @param string $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $this->setFileName($title);
        if (isset($model->idempresa)) {
            $this->template->setEmpresa($model->idempresa);
        }
        $this->template->setHeaderTitle($title);

        $this->template->initMpdf();
        $this->template->initHtml();

        $this->serviceData($model, $columns);
        $this->descriptionData($model);
        $this->materialData($model);
        $this->solutionData($model);
        $this->observationData($model);
        $this->machineData($model);
        $this->workData($model);
        $this->footerData($model);
        return false;
    }

    protected function descriptionData(ServicioAT $model): void
    {
        if (empty($model->descripcion)) {
            return;
        }

        $headers = [Tools::lang()->trans('description')];
        $rows = [[nl2br($model->descripcion)]];
        $this->addTablePage($headers, $rows, [], '');
    }

    protected function footerData(ServicioAT $model): void
    {
        $this->template->writeHTML(nl2br(Tools::settings('servicios', 'print_pdf_footer_text', '')));
    }

    protected function machineData(ServicioAT $model): void
    {
        if (false === Tools::settings('servicios', 'print_pdf_machine_info', false)) {
            return;
        }

        $headers = [
            Tools::lang()->trans('name'),
            Tools::lang()->trans('serial-number'),
            Tools::lang()->trans('description'),
        ];

        $rows = [];
        foreach ($model->getMachines() as $machine) {
            $rows[] = [
                $machine->nombre,
                $machine->numserie,
                $machine->descripcion,
            ];
        }

        $this->addTablePage($headers, $rows, [], Tools::lang()->trans('machines'));
    }

    protected function materialData(ServicioAT $model): void
    {
        if (empty($model->material)) {
            return;
        }

        $headers = [Tools::lang()->trans('material')];
        $rows = [[nl2br($model->material)]];
        $this->addTablePage($headers, $rows, [], '');
    }

    protected function observationData(ServicioAT $model): void
    {
        if (empty($model->observaciones)
            || false === Tools::settings('servicios', 'print_pdf_observations', false)) {
            return;
        }

        $headers = [Tools::lang()->trans('observations')];
        $rows = [[nl2br($model->observaciones)]];
        $this->addTablePage($headers, $rows, [], '');
    }

    protected function serviceData(ServicioAT $model, array $columns): void
    {
        $excludeFields = ['idmaquina', 'idtipo', 'neto', 'codalmacen', 'idprioridad', 'material', 'descripcion', 'solucion', 'observaciones'];

        if (false === Tools::settings('servicios', 'print_pdf_agent', false)) {
            $excludeFields[] = 'codagente';
        }

        if (false === Tools::settings('servicios', 'print_pdf_assigned', false)) {
            $excludeFields[] = 'asignado';
        }

        $dataModel = $this->getModelColumnsData($model, $columns);
        foreach ($excludeFields as $field) {
            if (isset($dataModel[$field])) {
                unset($dataModel[$field]);
            }
        }

        $subject = $model->getSubject();
        $tipoidfiscal = empty($subject->tipoidfiscal) ? Tools::lang()->trans('cifnif') : $subject->tipoidfiscal;
        $dataModel[$tipoidfiscal] = [
            'title' => $tipoidfiscal,
            'value' => $subject->cifnif,
        ];

        $dataModel['address'] = [
            'title' => Tools::lang()->trans('address'),
            'value' => $subject->getDefaultAddress()->direccion,
        ];

        $this->template->addDualColumnTable($dataModel);
    }

    protected function solutionData(ServicioAT $model): void
    {
        if (empty($model->solucion)) {
            return;
        }

        $headers = [Tools::lang()->trans('solution')];
        $rows = [[nl2br($model->solucion)]];
        $this->addTablePage($headers, $rows, [], '');
    }

    protected function workData(ServicioAT $model): void
    {
        if (false === Tools::settings('servicios', 'print_pdf_works', false)) {
            return;
        }

        $headers = [
            Tools::lang()->trans('from-date'),
            Tools::lang()->trans('from-hour'),
            Tools::lang()->trans('until-date'),
            Tools::lang()->trans('until-hour'),
            Tools::lang()->trans('observations'),
        ];

        if (Tools::settings('servicios', 'print_pdf_work_reference', false)) {
            $headers[] = Tools::lang()->trans('reference');
        }

        if (Tools::settings('servicios', 'print_pdf_work_description', false)) {
            $headers[] = Tools::lang()->trans('description');
        }

        if (Tools::settings('servicios', 'print_pdf_work_quantity', false)) {
            $headers[] = Tools::lang()->trans('quantity');
        }

        if (Tools::settings('servicios', 'print_pdf_work_price', false)) {
            $headers[] = Tools::lang()->trans('price');
        }

        $rows = [];
        foreach ($model->getTrabajos() as $work) {
            $dataWork = [
                $work->fechainicio,
                $work->horainicio,
                $work->fechafin,
                $work->horafin,
                nl2br($work->observaciones)
            ];

            if (Tools::settings('servicios', 'print_pdf_work_reference', false)) {
                $dataWork[] = $work->referencia;
            }

            if (Tools::settings('servicios', 'print_pdf_work_description', false)) {
                $dataWork[] = $work->descripcion;
            }

            if (Tools::settings('servicios', 'print_pdf_work_quantity', false)) {
                $dataWork[] = $work->cantidad;
            }

            if (Tools::settings('servicios', 'print_pdf_work_price', false)) {
                $dataWork[] = Tools::money($work->precio);
            }

            $rows[] = $dataWork;
        }

        $this->addTablePage($headers, $rows, [], Tools::lang()->trans('works'));
    }
}