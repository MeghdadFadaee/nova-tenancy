<?php

namespace MeghdadFadaee\NovaTenancy\Exceptions;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Laravel\Nova\Nova;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class TenantNotSelected extends RuntimeException implements Responsable
{
    public function __construct()
    {
        parent::__construct(__('nova-tenancy::nova-tenancy.errors.selection_required'));
    }

    public function toResponse($request): Response
    {
        if ($request instanceof Request && $request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'tenant_selection_required',
            ], 409);
        }

        $novaPath = '/'.trim(Nova::path(), '/');
        $toolPath = trim((string) config('nova-tenancy.routes.uri', 'nova-tenancy'), '/');

        return redirect()->to("{$novaPath}/{$toolPath}");
    }
}
