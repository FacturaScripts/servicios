<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ServiceToInvoice;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Dinamic\Model\TrabajoAT;

/**
 * Endpoints de la API para las acciones de la ficha del servicio que no son CRUD de un modelo.
 *
 * POST /api/3/generarDocumentoServicioAT
 *   Genera un presupuesto, un albarán o una factura de cliente con los trabajos del servicio
 *   que tienen la acción correspondiente (5 hacer presupuesto, 3 hacer albarán, 1 hacer factura),
 *   igual que los botones de la ficha del servicio. Parámetros:
 *     - idservicio: servicio del que generar el documento.
 *     - tipo: presupuesto, albaran o factura.
 *
 * POST /api/3/calcularHorasTrabajoAT
 *   Calcula las horas de un trabajo a partir de su fecha y hora de inicio y fin y las guarda
 *   como cantidad, igual que el botón de calcular horas de la ficha. Parámetros:
 *     - idtrabajo: trabajo a calcular.
 *
 * Ambos rechazan los servicios no editables, como la ficha.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ApiServiciosAT extends ApiController
{
    /** Tipos de documento que se pueden generar y la acción de los trabajos que incluyen. */
    const DOCUMENT_TYPES = [
        'presupuesto' => TrabajoAT::STATUS_MAKE_ESTIMATION,
        'albaran' => TrabajoAT::STATUS_MAKE_DELIVERY_NOTE,
        'factura' => TrabajoAT::STATUS_MAKE_INVOICE,
    ];

    /** Recurso para generar documentos a partir de un servicio. */
    const RESOURCE_DOCUMENT = 'generarDocumentoServicioAT';

    /** Recurso para calcular las horas de un trabajo. */
    const RESOURCE_HOURS = 'calcularHorasTrabajoAT';

    /**
     * Calcula y guarda las horas de un trabajo.
     */
    protected function calculateHours(): void
    {
        $work = new TrabajoAT();
        $code = $this->request->request->get('idtrabajo', '');
        if (empty($code) || false === $work->load($code)) {
            $this->sendError(Tools::trans('record-not-found'), Response::HTTP_NOT_FOUND);
            return;
        }

        $service = $work->getServicio();
        if (false === $service->exists() || false === $service->editable) {
            $this->sendError(Tools::trans('not-allowed-modify'), Response::HTTP_FORBIDDEN);
            return;
        }

        $previous = $work->cantidad;
        $work->cantidad = $work->calculateHours();
        if (false === $work->save()) {
            $this->sendError(Tools::trans('record-save-error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json([
                'ok' => Tools::trans('record-updated-correctly'),
                'data' => array_merge($work->toArray(), ['cantidad_anterior' => $previous]),
            ]);
    }

    /**
     * Genera el documento indicado con los trabajos del servicio.
     */
    protected function generateDocument(): void
    {
        $type = (string)$this->request->request->get('tipo', '');
        if (false === array_key_exists($type, self::DOCUMENT_TYPES)) {
            $this->sendError('invalid-type', Response::HTTP_BAD_REQUEST, ['allowed' => array_keys(self::DOCUMENT_TYPES)]);
            return;
        }

        $service = new ServicioAT();
        $code = $this->request->request->get('idservicio', '');
        if (empty($code) || false === $service->load($code)) {
            $this->sendError(Tools::trans('record-not-found'), Response::HTTP_NOT_FOUND);
            return;
        }

        if (false === $service->editable) {
            $this->sendError(Tools::trans('not-allowed-modify'), Response::HTTP_FORBIDDEN);
            return;
        }

        ServiceToInvoice::clear();
        $done = match ($type) {
            'presupuesto' => ServiceToInvoice::estimation($service),
            'albaran' => ServiceToInvoice::deliveryNote($service),
            default => ServiceToInvoice::invoice($service),
        };

        if (false === $done) {
            // los avisos del proceso, como «no hay trabajos que facturar», van en el mensaje,
            // que es lo que muestran los clientes de la API
            $warnings = $this->getLogMessages();
            $message = empty($warnings) ? Tools::trans('record-save-error') : implode(' ', $warnings);
            $this->sendError($message, Response::HTTP_UNPROCESSABLE_ENTITY, ['warnings' => $warnings]);
            return;
        }

        $documents = [];
        foreach (ServiceToInvoice::generated() as $doc) {
            $documents[] = [
                'modelo' => $doc->modelClassName(),
                'id' => $doc->id(),
                'codigo' => $doc->codigo,
                'fecha' => $doc->fecha,
                'neto' => $doc->neto,
                'total' => $doc->total,
            ];
        }

        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json([
                'ok' => Tools::trans('record-updated-correctly'),
                'data' => [
                    'idservicio' => $service->idservicio,
                    'tipo' => $type,
                    'documentos' => $documents,
                ],
            ]);
    }

    /**
     * Mensajes de aviso y error registrados durante la petición.
     *
     * @return string[]
     */
    private function getLogMessages(): array
    {
        $messages = [];
        foreach (MiniLog::read('', ['warning', 'error', 'critical']) as $item) {
            $messages[] = $item['message'];
        }

        return array_values(array_unique($messages));
    }

    /**
     * Atiende la petición según el recurso de la URL.
     */
    protected function runResource(): void
    {
        if (false === in_array($this->request->method(), ['POST', 'PUT'], true)) {
            $this->sendError('method-not-allowed', Response::HTTP_METHOD_NOT_ALLOWED);
            return;
        }

        switch ($this->getUriParam(2)) {
            case self::RESOURCE_DOCUMENT:
                $this->generateDocument();
                return;

            case self::RESOURCE_HOURS:
                $this->calculateHours();
                return;

            default:
                $this->sendError('invalid-resource', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Envía una respuesta de error en el formato de los endpoints del core.
     *
     * @param string $message  mensaje o clave del error.
     * @param int    $httpCode código HTTP de la respuesta.
     * @param array  $extra    datos adicionales para la respuesta.
     */
    private function sendError(string $message, int $httpCode, array $extra = []): void
    {
        $this->response
            ->setHttpCode($httpCode)
            ->json(array_merge(['status' => 'error', 'message' => $message], $extra));
    }
}
