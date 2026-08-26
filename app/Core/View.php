<?php
namespace App\Core;

final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /** Render a view inside a layout and echo the result. */
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        echo self::capture($view, $data, $layout);
    }

    public static function capture(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, array_merge($data, ['content' => $content]));
    }

    /** Render a view file with no layout and return the markup. */
    public static function partial(string $view, array $data = []): string
    {
        $file = App::basePath('app/Views/' . $view . '.php');
        if (!is_file($file)) {
            throw new \RuntimeException("View [$view] not found at $file");
        }
        extract(array_merge(self::$shared, $data), EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
