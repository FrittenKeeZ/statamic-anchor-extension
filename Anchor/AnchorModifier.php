<?php

namespace Statamic\Addons\Anchor;

use Statamic\API\Page;
use Statamic\Extend\Modifier;

class AnchorModifier extends Modifier
{
    const PAGES_REGEX = '/page:([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i';

    /**
     * Convert page:uuid links to proper URLs.
     *
     * @param  string  $value    The value to be modified.
     * @param  array   $params   Any parameters used in the modifier.
     * @param  array   $context  Contextual values.
     * @return mixed
     */
    public function index(string $value, array $params, array $context)
    {
        $useAbsoluteUrl = filter_var(array_get($params, 0, false), FILTER_VALIDATE_BOOLEAN);
        $method = $useAbsoluteUrl ? 'absoluteUrl' : 'url';

        $value = preg_replace_callback(
            self::PAGES_REGEX,
            function (array $matches) use ($method) {
                return Page::find($matches[1])->$method();
            },
            $value
        );

        return $value;
    }
}
