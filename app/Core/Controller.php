<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function requirePost(): void
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            View::render('errors/405', ['title' => 'Método no permitido']);
            exit;
        }

        if (!Security::validToken(isset($_POST['_csrf']) ? (string)$_POST['_csrf'] : null)) {
            http_response_code(419);
            View::render('errors/419', ['title' => 'Formulario vencido']);
            exit;
        }
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    protected function render(string $template, array $data = []): void
    {
        View::render($template, $data);
    }
}
