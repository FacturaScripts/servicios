<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Lib\Trait;

use FacturaScripts\Dinamic\Model\AgenciaTransporte;
use FacturaScripts\Dinamic\Model\Atributo;
use FacturaScripts\Dinamic\Model\AtributoValor;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\CuentaBanco;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Diario;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\EstadoAT;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Dinamic\Model\MaquinaAT;
use FacturaScripts\Dinamic\Model\PrioridadAT;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\TipoAT;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\Servicios\Model\ServicioATLog;
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;

trait MacroTrait {
    
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected static function installDependencies()
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();

        new User();
        new Cliente();
        // core tables and dependencies
        new AttachedFile();
        new Empresa();
        new Page();

        new Tarifa();
        new GrupoClientes();
        new Retencion();

        new Diario();
        new Serie();

        new CuentaBanco();
        new FormaPago();

        new Fabricante();
        new Familia();
        new Impuesto();
        new Producto();

        new Atributo();
        new AtributoValor();
        new Variante();

        new Agente();
        new Almacen();

        new User();
        new Cliente();

        new EstadoDocumento();
        new Divisa();
        new Ejercicio();
        new FormaPago();
        new Serie();
        new AgenciaTransporte();

        // plugin tables
        new MaquinaAT();
        new EstadoAT();
        new PrioridadAT();
        new TipoAT();
        new ServicioAT();
        new ServicioATLog();
        new TrabajoAT();
        new Stock();
    }
}