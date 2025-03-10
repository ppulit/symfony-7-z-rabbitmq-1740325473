<?php

namespace App\Controller;

use App\Form\FileUploadType;
use App\Message\ImportClientsMessage;
use App\Repository\ImportHistoryRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ClientsController extends AbstractController
{
    #[Route('/clients', name: 'app_clients')]
    public function index(
        Request $request,
        SluggerInterface $slugger,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        #[Autowire('%kernel.project_dir%/uploads')] string $filesDirectory,
    ): Response {
        $form = $this->createForm(FileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $file = $form->get('file')->getData();

                if ($file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                    try {
                        $file->move($filesDirectory, $newFilename);
                    } catch (FileException $e) {
                        $logger->error($e->getMessage());

                        return new JsonResponse([
                            'success' => false,
                            'error' => 'Wystąpił błąd podczas zapisu pliku.',
                        ]);
                    }

                    $messageBus->dispatch(
                        new ImportClientsMessage(
                            $filesDirectory.'/'.$newFilename,
                            $newFilename
                        )
                    );

                    $topic = 'progress_'.md5($newFilename);

                    return new JsonResponse([
                        'success' => true,
                        'topic' => $topic,
                    ]);
                }
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Formularz zawiera błędy.',
                ]);
            }
        }

        return $this->render('clients/index.html.twig', [
            'controller_name' => 'ClientsController',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/clients/last-import-summary', name: 'import_summary')]
    public function summary(ImportHistoryRepository $repository): Response
    {
        $lastImport = $repository->getLastImport();

        return $this->render('clients/summary.html.twig', [
            'lastImport' => $lastImport ? [
                'processed' => $lastImport->getProcessed(),
                'errors' => $lastImport->getErrors(),
                'status' => $lastImport->getStatus(),
                'errorMessages' => json_decode($lastImport->getErrorMessages(), true) ?? [],
                'createdAt' => $lastImport->getCreatedAt(),
            ] : null,
        ]);
    }
}
