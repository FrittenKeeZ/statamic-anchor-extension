<?php

namespace Statamic\Addons\Anchor;

use Statamic\API\Content;
use Statamic\API\Page;
use Statamic\Extend\Listener;

class AnchorListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
        'cp.add_to_head' => 'handle',
    ];

    /**
     * Add config to window object on page.create and page.edit urls.
     *
     * @return string
     */
    public function handle()
    {
        // Add Pages tree to config.
        $config = [
            'options' => [
                'customClassOption'     => $this->getConfig('custom_class_option'),
                'customClassOptionText' => $this->trans('options.custom_class_option_text'),
                'linkValidation'        => $this->getConfigBool('link_validation'),
                'placeholderText'       => $this->trans('options.placeholder_text'),
                'targetCheckbox'        => $this->getConfigBool('target_checkbox'),
                'targetCheckboxText'    => $this->trans('options.target_checkbox_text'),
            ],
            'pages'   => [
                'inline' => $this->generateInlinePagesOptions($this->getTree())->all(),
                'nested' => $this->generateNestedPagesOptions($this->getTree())->all(),
            ],
            'types'   => [
                ['value' => 'intern', 'text' => $this->trans('options.select_type_intern')],
                ['value' => 'extern', 'text' => $this->trans('options.select_type_extern')],
            ],
        ];

        // Add empty option.
        array_unshift($config['pages']['nested'], [
            'value' => '',
            'text'  => $this->trans('options.select_link_empty'),
        ]);

        $html = sprintf(
            '<link rel="stylesheet" href="%s" />' .
            '<script>(function(w,l,a,c){w[l]=w[l]||{},w[l][a]=c})(window,\'Addons\',\'Anchor\',%s)</script>',
            $this->css->url('styles.css'),
            json_encode($config)
        );

        return $html;
    }

    /**
     * Generate inline pages options recursively.
     *
     * @param  array   $tree
     * @param  string  $indent
     * @return \Illuminate\Support\Collection
     */
    protected function generateInlinePagesOptions(array $tree, $indent = '')
    {
        return collect($tree)->flatMap(function (array $item) use ($indent) {
            $suggestion = [
                'page:' . $item['page']->id() => $indent . $item['page']->get('title'),
            ];

            return collect($suggestion)->merge($this->generateInlinePagesOptions(
                array_get($item, 'children'),
                $indent . $item['page']->get('title') . ' / '
            ));
        });
    }

    /**
     * Generate nested pages options recursively.
     *
     * @param  array  $tree
     * @param  int    $depth
     * @return \Illuminate\Support\Collection
     */
    protected function generateNestedPagesOptions(array $tree, $depth = 0)
    {
        return collect($tree)->flatMap(function (array $item) use ($depth) {
            $indent = $depth > 0 ? str_repeat('  ', $depth - 1) . '↳ ' : '';

            $suggestion = [
                'value' => 'page:' . $item['page']->id(),
                'text'  => $indent . $item['page']->get('title'),
            ];

            return collect([$suggestion])->merge($this->generateNestedPagesOptions(
                array_get($item, 'children'),
                $depth + 1
            ));
        });
    }

    /**
     * Get the content tree.
     *
     * @return array
     */
    protected function getTree()
    {
        $tree = Content::tree('/');

        // Add the home page since it's not part of the content tree.
        array_unshift($tree, [
            'page'     => Page::whereUri('/'),
            'depth'    => 1,
            'children' => [],
        ]);

        return $tree;
    }
}
