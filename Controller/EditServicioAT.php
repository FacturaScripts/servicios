<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Controller;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ServiceToInvoice;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Dinamic\Model\TipoAT;
use FacturaScripts\Dinamic\Model\TrabajoAT;

/**
 * Description of EditServicioAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditServicioAT extends EditController
{
    use DocFilesTrait;

    public function getModelClassName(): string
    {
        return 'ServicioAT';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'service';
        $data['icon'] = 'fa-solid fa-headset';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Calculate the number of hours worked.
     *
     * @return bool
     */
    protected function calculateQuantity(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        }

        $model = new TrabajoAT();
        $code = $this->request->request->get('code', '');
        if (false === $model->loadFromCode($code)) {
            return true;
        }

        $days = $this->daysBetween($model->fechainicio, $model->fechafin);
        $hours = $this->TimeDifferenceInHours($model->horainicio, $model->horafin);
        $model->cantidad = ($days * 24) + $hours;
        if ($model->save()) {
            Tools::log()->notice('record-updated-correctly');
            return true;
        }

        Tools::log()->warning('record-save-error');
        return true;
    }

    /**
     * Create the view to display.
     *
     * @throws Exception
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('top');
        $this->createViewsWorks();
        $this->createViewsCategories();
        $this->createViewsChecks();
        $this->createViewDocFiles();
        $this->createViewsInvoices();
        $this->createViewsDeliveryNotes();
        $this->createViewsEstimations();
        $this->createViewLogs();
    }

    /**
     * Add the categories of the service view.
     *
     * @param string $viewName
     * @return void
     */
    protected function createViewsCategories(string $viewName = 'EditServicioCategoriaAT'): void
    {
        $this->addEditListView($viewName, 'ServicioCategoriaAT', 'categories', 'fa-solid fa-tags')
            ->setInLine(true);
    }

    /**
     * Add the checks of the service view.
     *
     * @param string $viewName
     * @return void
     */
    protected function createViewsChecks(string $viewName = 'EditServicioCheckAT'): void
    {
        $this->addEditListView($viewName, 'ServicioCheckAT', 'verifications', 'fa-solid fa-list-check')
            ->setInLine(true);
    }

    /**
     * @param string $viewName
     * @return void
     * @throws Exception
     */
    protected function createViewsDeliveryNotes(string $viewName = 'ListAlbaranCliente'): void
    {
        $this->addListView($viewName, 'AlbaranCliente', 'delivery-notes', 'fa-solid fa-dolly-flatbed')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addSearchFields(['codigo', 'numero', 'numero2', 'observaciones'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);

        $this->addButton($viewName, [
            'action' => 'make-delivery-note',
            'color' => 'warning',
            'confirm' => true,
            'icon' => 'fa-solid fa-magic',
            'label' => 'make-delivery-note'
        ]);
    }

    /**
     * @param string $viewName
     * @return void
     * @throws Exception
     */
    protected function createViewsEstimations(string $viewName = 'ListPresupuestoCliente'): void
    {
        $this->addListView($viewName, 'PresupuestoCliente', 'estimations', 'fa-regular fa-file-powerpoint')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addSearchFields(['codigo', 'numero', 'numero2', 'observaciones'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);

        $this->addButton($viewName, [
            'action' => 'make-estimation',
            'color' => 'warning',
            'confirm' => true,
            'icon' => 'fa-solid fa-magic',
            'label' => 'make-estimation'
        ]);
    }

    /**
     * @param string $viewName
     * @return void
     * @throws Exception
     */
    protected function createViewsInvoices(string $viewName = 'ListFacturaCliente'): void
    {
        $this->addListView($viewName, 'FacturaCliente', 'invoices', 'fa-solid fa-file-invoice-dollar')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addSearchFields(['codigo', 'numero', 'numero2', 'observaciones'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);

        $this->addButton($viewName, [
            'action' => 'make-invoice',
            'color' => 'warning',
            'confirm' => true,
            'icon' => 'fa-solid fa-magic',
            'label' => 'make-invoice'
        ]);
    }

    public function createViewLogs(string $viewName = 'ListServicioATLog'): void
    {
        $this->addListView($viewName, 'ServicioATLog', 'history', 'fa-solid fa-history')
            ->addOrderBy(['creationdate'], 'date', 2)
            ->addSearchFields(['context', 'message'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);
    }

    protected function createViewsWorks(string $viewName = 'EditTrabajoAT'): void
    {
        $this->addEditListView($viewName, 'TrabajoAT', 'work', 'fa-solid fa-stethoscope')
            ->disableColumn('service');
    }

    /**
     * Calculate number days between two dates
     *
     * @param string $start
     * @param string $end
     * @param bool $increment
     *
     * @return int
     */
    protected function daysBetween($start, $end, $increment = false): int
    {
        if (empty($start) || empty($end)) {
            return 0;
        }

        $diff = strtotime($end) - strtotime($start);
        $result = ceil($diff / 86400);
        if ($increment) {
            ++$result;
        }
        return $result;
    }

    protected function disableAllColumns(string $mainViewName, string $exclude = ''): void
    {
        // si no existe la vista, salimos
        if (!isset($this->views[$mainViewName])) {
            return;
        }

        foreach ($this->views[$mainViewName]->getColumns() as $group) {
            foreach ($group->columns as $col) {
                if ($col->name === $exclude || $col->display === 'none') {
                    continue;
                }

                $this->views[$mainViewName]->disableColumn($col->name, false, 'true');
            }
        }
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'auto-quantity':
                return $this->calculateQuantity();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'make-delivery-note':
                return $this->makeDeliveryNoteAction();

            case 'make-estimation':
                return $this->makeEstimationAction();

            case 'make-invoice':
                return $this->makeInvoiceAction();

            case 'unlink-file':
                return $this->unlinkFileAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     * @throws Exception
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $idservicio = $this->getViewModelValue($mainViewName, 'idservicio');

        switch ($viewName) {
            case $mainViewName:
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->model->codalmacen = $this->user->codalmacen;
                    $view->model->idempresa = $this->user->idempresa;
                    $view->model->nick = $this->user->nick;
                } elseif (false === $view->model->editable) {
                    $this->disableAllColumns($mainViewName, 'status');

                    if (false === array_key_exists('EditTrabajoAT', $this->views)) {
                        break;
                    }
                    $this->disableAllColumns('EditTrabajoAT');

                    // disable buttons
                    $this->setSettings('EditTrabajoAT', 'btnDelete', false);
                    $this->setSettings('EditTrabajoAT', 'btnNew', false);
                    $this->setSettings('EditTrabajoAT', 'btnSave', false);
                }

                // si no hay tipo, ocultamos el campo
                $type = new TipoAT();
                if ($type->count() === 0) {
                    $view->disableColumn('type');
                }

                $this->addButton($viewName, [
                    'action' => 'CopyModel?model=' . $this->getModelClassName() . '&code=' . $view->model->primaryColumnValue(),
                    'icon' => 'fa-solid fa-cut',
                    'label' => 'copy',
                    'type' => 'link'
                ]);
                break;

            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $idservicio);
                break;

            case 'ListServicioATLog':
                $where = [new DataBaseWhere('idservicio', $idservicio)];
                $orderBy = ['creationdate' => 'DESC'];
                $view->loadData('', $where, $orderBy);
                break;

            case 'EditServicioCategoriaAT':
            case 'EditServicioCheckAT':
                $where = [ new DataBaseWhere('idservice', $idservicio) ];
                $view->loadData('', $where);
                // Remove checks if the service has no categories and checks.
                if ($viewName === 'EditServicioCheckAT'
                    && $this->views['EditServicioCategoriaAT']->count === 0
                    && $this->views['EditServicioCheckAT']->count === 0
                ) {
                    unset($this->views['EditServicioCheckAT']);
                }
                break;

            case 'EditTrabajoAT':
                $where = [new DataBaseWhere('idservicio', $idservicio)];
                $orderBy = ['fechainicio' => 'DESC', 'horainicio' => 'DESC', 'idtrabajo' => 'DESC'];
                $view->loadData('', $where, $orderBy);
                $this->loadStatusWorkValues($viewName, $view);
                if ($view->count > 0) {
                    $this->addButton('EditTrabajoAT', [
                        'action' => 'auto-quantity',
                        'icon' => 'fa-solid fa-calculator',
                        'label' => 'calculate-hours'
                    ]);
                } elseif (false === $view->model->exists()) {
                    $view->model->codagente = $this->getViewModelValue($mainViewName, 'codagente');
                    $view->model->nick = $this->getViewModelValue($mainViewName, 'nick');
                }
                break;

            case 'ListAlbaranCliente':
            case 'ListFacturaCliente':
            case 'ListPresupuestoCliente':
                $where = [new DataBaseWhere('idservicio', $idservicio)];
                $view->loadData('', $where);
                break;
        }
    }

    protected function loadStatusWorkValues(string $viewName, BaseView $view): void
    {
        $column = $this->views[$viewName]->columnForName('action');
        if ($column && $column->widget->getType() === 'select') {
            $statuses = [];
            foreach ($view->model->getAvailableStatus() as $key => $value) {
                $statuses[] = ['value' => $key, 'title' => $value];
            }

            $column->widget->setValuesFromArray($statuses);
        }
    }

    protected function makeDeliveryNoteAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        }

        $service = new ServicioAT();
        $code = $this->request->get('code', '');
        if (false === $service->loadFromCode($code) || false === $service->editable) {
            return true;
        }

        if (false === ServiceToInvoice::deliveryNote($service)) {
            Tools::log()->warning('record-save-error');
            return true;
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    protected function makeEstimationAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        }

        $service = new ServicioAT();
        $code = $this->request->get('code', '');
        if (false === $service->loadFromCode($code) || false === $service->editable) {
            return true;
        }

        if (false === ServiceToInvoice::estimation($service)) {
            Tools::log()->warning('record-save-error');
            return true;
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    protected function makeInvoiceAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        }

        $service = new ServicioAT();
        $code = $this->request->get('code', '');
        if (false === $service->loadFromCode($code) || false === $service->editable) {
            return true;
        }

        if (false === ServiceToInvoice::invoice($service)) {
            Tools::log()->warning('record-save-error');
            return true;
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    /**
     * Calculate hours number between two times
     *
     * @param string $start
     * @param string $end
     *
     * @return float
     */
    protected function TimeDifferenceInHours($start, $end): float
    {
        if (empty($start) || empty($end)) {
            return 0.0;
        }

        $startHour = date_parse_from_format('H:i:s', $start);
        $endHour = date_parse_from_format('H:i:s', $end);

        $ini = ($startHour['hour'] * 3600) + ($startHour['minute'] * 60) + $startHour['second'];
        $fin = ($endHour['hour'] * 3600) + ($endHour['minute'] * 60) + $endHour['second'];

        $dif = ($fin - $ini) / 3600;
        return round($dif, 4);
    }
}
