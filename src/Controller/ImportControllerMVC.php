<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService   $auth,
        private ImportService $importService,
    ) {}

    #[Route('/admin-mvc/import', name: 'admin_import_mvc', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->auth->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/import/index.html.twig', [
            'result'    => null,
            'activeTab' => 'csv',
        ]);
    }

    #[Route('/admin-mvc/import/csv', name: 'admin_import_csv_mvc', methods: ['POST'])]
    public function importCsv(Request $request): Response
    {
        if (!$this->auth->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $table    = trim($request->request->get('table', ''));
        $pkColumn = trim($request->request->get('pk_column', '')) ?: null;
        $file     = $request->files->get('csv_file');

        if (!$file || !$file->isValid()) {
            return $this->render('admin/import/index.html.twig', [
                'result'    => ['error' => 'Fichier invalide ou manquant.'],
                'activeTab' => 'csv',
            ]);
        }

        $result = $this->importService->importCsv($table, $file, $pkColumn);

        return $this->render('admin/import/index.html.twig', [
            'result'    => $result,
            'activeTab' => 'csv',
        ]);
    }

    #[Route('/admin-mvc/import/sql', name: 'admin_import_sql_mvc', methods: ['POST'])]
    public function importSql(Request $request): Response
    {
        if (!$this->auth->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $file = $request->files->get('sql_file');

        if (!$file || !$file->isValid()) {
            return $this->render('admin/import/index.html.twig', [
                'result'    => ['error' => 'Fichier invalide ou manquant.'],
                'activeTab' => 'sql',
            ]);
        }

        $result = $this->importService->importSql($file);

        return $this->render('admin/import/index.html.twig', [
            'result'    => $result,
            'activeTab' => 'sql',
        ]);
    }
}
