<?php 

namespace rasteiner\publishem;

use Exception;
use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

/**
 * @param Page $page
 * @return array{'status':string, 'query':string, 'dialog':string, 'dropdown':string, 'button':string}|null
 */
function readOptions(Page $page): ?array {
    $options = $page->blueprint()->options()['publishem'] ?? null;

    if(is_string($options)) {
        $options = Str::lower($options);
        $status = $options;
    } else if($options === true) {
        // don't exit the function, but do nothing because all default settings will be used
    } else if(is_array($options)) {
        $status = $options['status'] ?? null;
        $query = $options['query'] ?? null;
        $dialog = $options['dialog'] ?? null;
        $dropdown = $options['dropdown'] ?? null;
        $button = $options['button'] ?? null;
    } else {
        return null;
    }

    $status ??= 'unlisted';
    $query ??= 'page.drafts';
    $dialog ??= t('rasteiner.publishem.dialog');
    $dropdown ??= t('rasteiner.publishem.dropdown');
    $button ??= t('rasteiner.publishem.button');
    
    if(A::has(['unlisted', 'listed'], $status) === false) {
        throw new Exception(t('rasteiner.publishem.invalid-option') . ' (status)');
    }

    return compact('status', 'query', 'dialog', 'dropdown', 'button');
}

function allErrors(Pages $pages) {
    $errors = [];
    foreach($pages as $page) {
        /**
         * @var Page $page
         */

        if(!$page->isValid()) {

            // compact error details
            $details = [];
            foreach($page->errors() as $key => $error) {
                $details[$key] = $error['label'] . ': ' . join(', ', A::wrap($error['message']));
            }

            $errors[] = [
                'label' => $page->title()->value(),
                'message' => $details
            ];
        }
    }

    return $errors;
}


Kirby::plugin('rasteiner/publishem', [
    'translations' => [
        'en' => [
            'rasteiner.publishem.page-not-found' => 'Page not found',
            'rasteiner.publishem.invalid-option' => 'Invalid publishem option',
            'rasteiner.publishem.pages' => 'Pages',
            'rasteiner.publishem.dialog' => 'Publish child pages',
            'rasteiner.publishem.button' => 'Publish',
            'rasteiner.publishem.dropdown' => 'Publish content',
            'rasteiner.publishem.error-dialog' => 'The following pages are not ready to be published',
        ],
        'it' => [
            'rasteiner.publishem.page-not-found' => 'Pagina non trovata',
            'rasteiner.publishem.invalid-option' => 'Opzione publishem non valida',
            'rasteiner.publishem.pages' => 'Pagine',
            'rasteiner.publishem.dialog' => 'Pubblica le sottopagine',
            'rasteiner.publishem.button' => 'Pubblica',
            'rasteiner.publishem.dropdown' => 'Pubblica contenuto',
            'rasteiner.publishem.error-dialog' => 'Le seguenti pagine non sono pronte per essere pubblicate',
        ],
    ],
    'areas' => [
        'site' => function($kirby) {
            return [
                'dropdowns' => [
                    'page' => function(string $id) {
                        $page = Find::page($id);
                        if(!$page) {
                            throw new Exception(t('rasteiner.publishem.page-not-found'));
                        }

                        $default = $page->panel()->dropdown();
                        $options = readOptions($page);
                        if(!$options) {
                            return $default;
                        }
   
                        return array_merge([
                            [
                                'icon' => 'sitemap',
                                'text' => $options['dropdown'],
                                'dialog' => 'publishem/' . $id,
                            ], 
                            '-'
                        ], $default);
                    }
                ],
                'dialogs' => [
                    'publishem' => [
                        'pattern' => 'publishem/(:any)',
                        'load' => function(string $id) {
                            $page = Find::page($id);
                            $options = readOptions($page);
                            if(!$options) {
                                throw new Exception(t('rasteiner.publishem.invalid-option'));
                            }

                            $pages = $page->query($options['query'], Pages::class);
                            $errors = allErrors($pages);
                            if(count($errors) > 0) {
                                return [
                                    'component' => 'k-error-dialog',
                                    'props' => [
                                        'message' => t('rasteiner.publishem.error-dialog'),
                                        'details' => $errors
                                    ]
                                ];
                            } else {
                                return [
                                    'component' => 'k-form-dialog',
                                    'props' => [
                                        'submitButton' => t('rasteiner.publishem.button'),
                                        'icon' => 'bolt',
                                        'text' => $options['dialog'],
                                        'fields' => [
                                            'pages' => [
                                                'type' => 'pages',
                                                'label' => t('rasteiner.publishem.pages'),
                                                'subpages' => false,
                                                'pages' => $pages->values(),
                                                'sort' => 'false',
                                                'disabled' => 'true',
                                            ]
                                        ],
                                        'value' => [
                                            'pages' => $pages->values(fn(Page $p) => $p->panel()->pickerData())
                                        ]
                                    ]
                                ];
                            }
                        },
                        'submit' => function(string $id) {
                            $page = Find::page($id);
                            if(!$page) {
                                throw new Exception(t('rasteiner.publishem.page-not-found'));
                            }

                            $options = readOptions($page);
                            if(!$options) {
                                throw new Exception(t('rasteiner.publishem.invalid-option'));
                            }

                            /**
                             * @var Pages $pages
                             */
                            $pages = $page->query($options['query'], Pages::class);
                            
                            foreach($pages as $child) {
                                /**
                                 * @var Page $child
                                 */

                                $child->changeStatus($options['status']);
                            }

                            return true;
                        }
                    ]
                ]
            ];
        }
    ]
]);