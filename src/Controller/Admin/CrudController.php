<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Service\CrudService;

/**
 * List / create / edit / delete for every entity defined in config/entities.php.
 */
class CrudController
{
    public function __construct(
        protected readonly TemplateRenderer $view,
        protected readonly CrudService $crud,
        protected readonly Flash $flash,
    ) {
    }

    public function list(Request $request, string $entity): Response
    {
        $definition = $this->crud->definition($entity);
        if ($definition === null) {
            return $this->notFound();
        }
        return $this->view->render('admin/list.latte', [
            'entity' => $entity,
            'titles' => $this->crud->titles(),
            'title' => $definition['title'],
            'canCreate' => $definition['canCreate'] ?? true,
            'headers' => array_column($definition['columns'], 1),
            'rows' => $this->crud->listRows($entity),
        ]);
    }

    public function createForm(Request $request, string $entity): Response
    {
        if (!$this->canCreate($entity)) {
            return $this->notFound();
        }
        return $this->form($entity, null, null, []);
    }

    public function create(Request $request, string $entity): Response
    {
        if (!$this->canCreate($entity)) {
            return $this->notFound();
        }
        [$data, $errors] = $this->crud->collect($entity, $request, null);
        if ($errors !== []) {
            return $this->form($entity, null, $data, $errors);
        }
        $this->crud->save($entity, $data, null);
        $this->flash->add('Saved.');
        return Response::redirect("/admin/{$entity}");
    }

    public function editForm(Request $request, string $entity, int $id): Response
    {
        $row = $this->crud->definition($entity) === null ? null : $this->crud->repository($entity)->find($id);
        if ($row === null) {
            return $this->notFound();
        }
        return $this->form($entity, $id, $row, []);
    }

    public function update(Request $request, string $entity, int $id): Response
    {
        $row = $this->crud->definition($entity) === null ? null : $this->crud->repository($entity)->find($id);
        if ($row === null) {
            return $this->notFound();
        }
        [$data, $errors] = $this->crud->collect($entity, $request, $row);
        if ($errors !== []) {
            return $this->form($entity, $id, $data, $errors);
        }
        $this->crud->save($entity, $data, $id);
        $this->flash->add('Saved.');
        return Response::redirect("/admin/{$entity}");
    }

    public function delete(Request $request, string $entity, int $id): Response
    {
        if ($this->crud->definition($entity) === null) {
            return $this->notFound();
        }
        $blocker = $this->crud->deleteBlocker($entity, $id);
        if ($blocker !== null) {
            $this->flash->add($blocker);
        } else {
            $this->crud->repository($entity)->delete($id);
            $this->flash->add('Deleted.');
        }
        return Response::redirect("/admin/{$entity}");
    }

    /**
     * @param array<string, mixed>|null $values
     * @param list<string> $errors
     */
    private function form(string $entity, ?int $id, ?array $values, array $errors): Response
    {
        return $this->view->render('admin/form.latte', [
            'entity' => $entity,
            'titles' => $this->crud->titles(),
            'title' => $this->crud->definition($entity)['title'],
            'id' => $id,
            'fields' => $this->crud->formFields($entity, $values),
            'errors' => $errors,
        ], $errors === [] ? 200 : 422);
    }

    private function canCreate(string $entity): bool
    {
        $definition = $this->crud->definition($entity);
        return $definition !== null && ($definition['canCreate'] ?? true);
    }

    protected function notFound(): Response
    {
        return $this->view->render('admin/not-found.latte', ['titles' => $this->crud->titles()], 404);
    }
}
