<?php

namespace FacturaScripts\Plugins\Servicios\Mod;

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;

class SalesHeaderHTMLMod implements SalesModInterface
{

    public function apply(SalesDocument &$model, array $formData, User $user)
    {
        // TODO: Implement apply() method.
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {
        // TODO: Implement applyBefore() method.
    }

    public function assets(): void
    {
        // TODO: Implement assets() method.
    }

    public function newFields(): array
    {
        return ['servicio'];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        switch ($field) {
            case 'servicio':
                return $this->servicio($i18n, $model);
        }

        return null;
    }

    private static function servicio(Translator $i18n, SalesDocument $model): string
    {
        if ($model->modelClassName() === 'PedidoCliente') {
            return '';
        }

        return empty($model->{'idservicio'}) ? '' : '<div class="col-sm-6">'
            . '<div class="form-group">'
            . '<a href="/EditServicioAT?code=' . $model->{'idservicio'} . '">' . $i18n->trans('service') . '</a>'
            . '<input type="text" value="' . $model->{'idservicio'} . '" class="form-control" disabled />'
            . '</div>'
            . '</div>';
    }
}