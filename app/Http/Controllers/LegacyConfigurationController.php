<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyMailConfigurationServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Http\Requests\UpdateLegacyConfigurationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class LegacyConfigurationController extends Controller
{
    public function index(
        LegacyMailConfigurationServiceInterface $mailConfiguration,
        LegacyNavigationServiceInterface $navigation
    ): View {
        return view('configurations.index', [
            'mailConfiguration' => $mailConfiguration->current(),
            'menuOrder' => $navigation->currentOrder(),
            'menuItems' => $navigation->editorItems(),
        ]);
    }

    public function update(
        UpdateLegacyConfigurationRequest $request,
        LegacyMailConfigurationServiceInterface $mailConfiguration,
        LegacyNavigationServiceInterface $navigation
    ): RedirectResponse {
        try {
            $mailConfiguration->save($request->toDto());
            $navigation->saveOrder($request->toMenuOrderDto());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput($request->except('mail_password'))
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.configuracoes.index')
            ->with('status', 'Configurações salvas com sucesso.')
            ->with('status_type', 'success');
    }
}
