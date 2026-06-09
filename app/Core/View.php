<?php
namespace App\Core;

class View
{
    private static array $shared = [];

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $data = array_merge(self::$shared, [
            'meta_title'       => null,
            'meta_description' => null,
            'meta_og_image'    => null,
            'meta_canonical'   => null,
            'og_type'          => null,
            'schema_org_json'  => null,
            'page'             => $template,
        ], $data);

        $viewFile = Application::$basePath . '/app/Views/' . $template . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: $template");
        }

        // render content first
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
            echo $content;
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $layoutFile = Application::$basePath . '/app/Views/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    public static function partial(string $template, array $data = []): void
    {
        $file = Application::$basePath . '/app/Views/' . $template . '.php';
        if (!is_file($file)) return;
        extract($data, EXTR_SKIP);
        require $file;
    }
}
