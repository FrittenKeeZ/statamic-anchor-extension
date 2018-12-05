<?php

namespace Statamic\Addons\Anchor\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Statamic\API\Config;
use Statamic\API\YAML;
use Statamic\Extend\Command;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anchor:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate anchor links to built-in Bard format as of 2.11.3';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (version_compare(STATAMIC_VERSION, '2.11.3', '<')) {
            $this->info('Please upgrade Statamic to version 2.11.3 before running this command.');
            return;
        }

        $config = Config::all();
        if (! isset($config['system']['filesystems']['content']['root'])) {
            $this->error('Content root was not found in system configuration.');
            return;
        }

        $contentPath = realpath(root_path($config['system']['filesystems']['content']['root']));
        $this->info(sprintf('Searching for all yaml and md files in `%s`.', $contentPath));
        $files = $this->getFilesList($contentPath, '/^.+\.(?:yaml|md)$/i');
        $bar = $this->output->createProgressBar(count($files));
        foreach ($files as $filename) {
            $this->replaceWithRegex(
                $filename,
                '/page:([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i',
                '{{ link:$1 }}'
            );
            $bar->advance();
        }
        $bar->finish();
        $this->line('');

        // Cleanup themes.
        if (isset($config['system']['filesystems']['themes']['root'])) {
            $themePath = realpath(
                root_path($config['system']['filesystems']['themes']['root'] . '/' . $config['theming']['theme'])
            );
            if ($themePath && $this->confirm('Do you want me to remove any anchor modifers in templates?', true)) {
                $files = $this->getFilesList($themePath, '/^.+\.html$/i');
                $bar = $this->output->createProgressBar(count($files));
                foreach ($files as $filename) {
                    $this->replaceWithRegex($filename, '/\s*\|\s*anchor(?::\w+)|\sanchor="\w+"/i', '');
                    $bar->advance();
                }
                $bar->finish();
                $this->line('');
            }
        }

        // Cleanup fieldsets.
        $fieldsetsPath = realpath(settings_path('fieldsets'));
        if ($fieldsetsPath && $this->confirm('Do you want me to enable internal links in Bard fields?', true)) {
            $cleanup = $this->confirm('Should I also remove obsolete Bard options?', true);
            $files = $this->getFilesList($fieldsetsPath, '/^.+\.yaml$/i');
            $bar = $this->output->createProgressBar(count($files));
            foreach ($files as $filename) {
                $this->updateOptions($filename, $cleanup);
                $bar->advance();
            }
            $bar->finish();
            $this->line('');
        }

        // Cleanup settings.
        $settingsPath = realpath(settings_path('addons/anchor.yaml'));
        if ($settingsPath && $this->confirm('Do you want me to remove the overridden anchor settings?', true)) {
            if (unlink($settingsPath)) {
                $this->info(sprintf('Settings file located at `%s` was removed successfully!', $settingsPath));
            }
        }

        $this->info('Migration complete!');
        $this->info('You can now uninstall `Anchor` by removing the addon folder.');
        $this->info('Rmember to run `php please clear:cache` for the conversion to take effect.');
    }

    /**
     * Get files list.
     *
     * @param string $path
     * @param string $pattern
     *
     * @return array
     */
    protected function getFilesList($path, $pattern)
    {
        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path)
            ),
            $pattern,
            RegexIterator::GET_MATCH
        );
        $files = array_keys(iterator_to_array($iterator));

        return $files;
    }

    /**
     * Replace content in file according to specified patterns.
     *
     * @param string $filename
     * @param string $search
     * @param string $replace
     *
     * @return void
     */
    protected function replaceWithRegex($filename, $search, $replace)
    {
        $content = file_get_contents($filename);
        $content = preg_replace($search, $replace, $content);
        file_put_contents($filename, $content);
    }

    /**
     * Update Bard options in file to enable internal links.
     *
     * @param string $filename
     * @param bool   $cleanup
     *
     * @return void
     */
    protected function updateOptions($filename, $cleanup)
    {
        $data = YAML::parse(file_get_contents($filename));
        $data = $this->updateOptionsRecursively($data, $cleanup);
        file_put_contents($filename, YAML::dump($data));
    }

    /**
     * Update Bard options recursively.
     *
     * @param mixed $data
     * @param bool  $cleanup
     *
     * @return mixed
     */
    protected function updateOptionsRecursively($data, $cleanup)
    {
        if (! is_array($data)) {
            return $data;
        }

        if (isset($data['type']) && $data['type'] === 'bard') {
            $data['allow_internal_links'] = true;
            // Add 'removeformat' to buttons for convenience.
            if (isset($data['buttons'])) {
                $data['buttons'] = array_unique(array_merge($data['buttons'], ['removeformat']));
            }
            if ($cleanup) {
                unset(
                    $data['autoLink'],
                    $data['autolink'],
                    $data['link_validation'],
                    $data['force_plain_text'],
                    $data['clean_pasted_html']
                );
            }
        }

        foreach ($data as &$item) {
            $item = $this->updateOptionsRecursively($item, $cleanup);
        }

        return $data;
    }
}
