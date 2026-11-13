<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], bool $layout = true): void
    {
        $viewPath = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_file($viewPath)) {
            throw new \RuntimeException('No existe la vista solicitada: ' . $template);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $viewPath;
            $content = (string)ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        if (!$layout) {
            echo $content;
            return;
        }

        $route = (string)($_GET['r'] ?? 'dashboard');
        require dirname(__DIR__) . '/Views/layout.php';
    }

    public static function redirect(string $route, array $parameters = []): void
    {
        header('Location: ' . Security::url($route, $parameters), true, 303);
        exit;
    }
}
