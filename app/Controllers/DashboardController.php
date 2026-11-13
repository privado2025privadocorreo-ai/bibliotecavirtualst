<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Loan;
use App\Models\Staff;
use Throwable;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $summary = [];
        $institution = [];
        try {
            $user = Auth::user();
            $summary = (new Loan())->dashboard((int)$user['institucion_id']);
            $institution = (new Staff())->institution((int)$user['institucion_id']);
        } catch (Throwable $exception) {
            error_log('Dashboard query failed: ' . $exception->getMessage());
            http_response_code(503);
            $this->flash('error', 'No se pudieron cargar los datos. Revisa la conexión y configuración de la base de datos.');
        }

        $this->render('dashboard/index', [
            'title' => 'Inicio',
            'summary' => $summary,
            'institution' => $institution,
            'user' => Auth::user(),
        ]);
    }
}
