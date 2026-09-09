<?php

namespace MeghdadFadaee\NovaTenancy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Contracts\TenantProvider;
use MeghdadFadaee\NovaTenancy\Support\TenantPayload;

class TenantIndexController extends Controller
{
    public function __invoke(
        Request $request,
        TenantProvider $provider,
        CurrentTenantContext $currentTenant,
    ): JsonResponse {
        $maximum = max(1, (int) config('nova-tenancy.selection.max_per_page', 48));
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', "max:{$maximum}"],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = min(
            $maximum,
            max(1, (int) ($validated['perPage'] ?? config('nova-tenancy.selection.per_page', 12))),
        );
        $query = $provider->query($request);

        if ($search !== '') {
            $query = $provider->search($request, $query, $search);
        }

        $paginator = $query->paginate($perPage)->through(TenantPayload::fromModel(...));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'currentKey' => $currentTenant->id(),
        ]);
    }
}
