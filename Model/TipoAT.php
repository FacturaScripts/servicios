<?php

namespace FacturaScripts\Plugins\Servicios\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Description of TipoAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TipoAT extends Base\ModelClass
{

    use Base\ModelTrait;

    public $id;

    /**
     * @var string
     */
    public $tipo;

    /**
     * @var bool
     */
    public $predeterminado;

    public function clear()
    {
        parent::clear();
        $this->predeterminado = true;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        if ($this->predeterminado) {
            $where = [
                new DataBaseWhere('predeterminado', true),
                new DataBaseWhere('id', $this->id, '!=')
            ];
            foreach ($this->all($where) as $type) {
                $type->predeterminado = false;
                $type->save();
            }
        }

        return true;
    }

    public static function tableName(): string
    {
        return 'serviciosat_tipos';
    }

    public function test(): bool
    {
        $this->tipo = $this->toolBox()->utils()->noHtml($this->tipo);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListServicioAT?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}