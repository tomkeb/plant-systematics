<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\PhotoCategoryRepository;
use App\Repository\PhotoRepository;
use App\Service\CrudService;
use App\Service\PhotoService;

/**
 * Photo upload (with resizing) and file-aware deletion; editing metadata reuses CrudController.
 */
final class PhotoAdminController extends CrudController
{
    public function __construct(
        TemplateRenderer $view,
        CrudService $crud,
        Flash $flash,
        private readonly PhotoService $photoService,
        private readonly PhotoRepository $photos,
        private readonly PhotoCategoryRepository $categories,
    ) {
        parent::__construct($view, $crud, $flash);
    }

    public function uploadForm(Request $request): Response
    {
        return $this->renderUpload([]);
    }

    public function upload(Request $request): Response
    {
        $files = array_filter($request->files('photos'), fn(array $f) => $f['error'] !== UPLOAD_ERR_NO_FILE);
        if ($files === []) {
            return $this->renderUpload(['Choose at least one file.']);
        }

        $categoryIds = array_map('intval', $request->postList('categoryIds'));
        $visible = $request->postFlag('visible');
        $author = $request->post('author');
        $stored = 0;
        $errors = [];
        foreach ($files as $file) {
            try {
                $filename = $this->photoService->store($file);
            } catch (\RuntimeException $e) {
                $errors[] = $e->getMessage();
                continue;
            }
            $this->photos->insert([
                'filename' => $filename,
                'name' => pathinfo($file['name'], PATHINFO_FILENAME),
                'nameEn' => '',
                'desc' => '',
                'descEn' => '',
                'author' => $author,
                'categoryIds' => $categoryIds,
                'date' => date('Y-m-d'),
                'visible' => $visible,
            ] + $this->crud->autoValues('photos', true));
            $stored++;
        }

        if ($errors !== []) {
            if ($stored > 0) {
                $this->flash->add("Uploaded {$stored} photo(s).");
            }
            return $this->renderUpload($errors, 422);
        }
        $this->flash->add("Uploaded {$stored} photo(s). Edit them to add names and descriptions.");
        return Response::redirect('/admin/photos');
    }

    public function updatePhoto(Request $request, int $id): Response
    {
        $photo = $this->photos->find($id);
        if ($photo === null) {
            return $this->notFound();
        }
        [$data, $errors] = $this->crud->collect('photos', $request, $photo);

        $files = array_values(array_filter($request->files('file'), fn(array $f) => $f['error'] !== UPLOAD_ERR_NO_FILE));
        if ($errors === [] && $files !== []) {
            try {
                $this->photoService->replace($photo['filename'], $files[0]);
            } catch (\RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($errors !== []) {
            return $this->form('photos', $id, $data, $errors);
        }

        $this->crud->save('photos', $data, $id);
        $this->flash->add($files === [] ? 'Saved.' : 'Saved, photo replaced (reload the page if the old image still shows).');
        return Response::redirect('/admin/photos');
    }

    public function deletePhoto(Request $request, int $id): Response
    {
        $photo = $this->photos->find($id);
        if ($photo === null) {
            return $this->notFound();
        }
        $this->photoService->delete($photo['filename']);
        $this->photos->delete($id);
        $this->flash->add('Deleted.');
        return Response::redirect('/admin/photos');
    }

    /**
     * @param list<string> $errors
     */
    private function renderUpload(array $errors, int $status = 200): Response
    {
        $categories = [];
        foreach ($this->categories->all(['name' => 'asc']) as $category) {
            $categories[] = ['id' => (string) $category['_id'], 'name' => $category['name']];
        }
        return $this->view->render('admin/photo-upload.latte', [
            'titles' => $this->crud->titles(),
            'categories' => $categories,
            'errors' => $errors,
        ], $status);
    }
}
