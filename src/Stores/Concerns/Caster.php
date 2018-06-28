<?php
declare(strict_types=1);

namespace BwtTeam\LaravelRequestLogger\Stores\Concerns;

use Illuminate\Contracts\Support\Renderable;
use WyriHaximus\HtmlCompress\Factory as HtmlCompressFactory;

trait Caster
{
    /**
     * Cast the given value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function castField($value)
    {
        $config = config('request-logger.casts');

        if (is_array($value)) {
            return array_map([$this, 'castField'], $value);
        }

        if ($value instanceof Renderable) {
            $html = $value->render();

            if ($config['compress_html']) {
                $html = HtmlCompressFactory::construct()->compress($html);
            }

            return $html;
        }

        return $value;
    }
}