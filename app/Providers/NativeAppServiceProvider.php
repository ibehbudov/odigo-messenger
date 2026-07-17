<?php

namespace App\Providers;

use App\Http\Controllers\OdigoController;
use Native\Laravel\Facades\Window;
use Native\Laravel\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Odigo is a multi-window app: each panel opens as its own frameless
     * native window that can be dragged anywhere on the desktop, like the
     * original Odigo client. Windows sync via a BroadcastChannel in the browser.
     */
    public function boot(): void
    {
        foreach (OdigoController::windows() as $name => $cfg) {
            Window::open($name)
                ->title($cfg['title'])
                // Must be an ABSOLUTE url — NativePHP passes it straight to Electron's
                // loadURL(); a bare "/w/..." path yields a blank window. url() builds the
                // internal-server absolute URL (same as the default window's url('/')).
                ->url(url('/w/' . $name))
                ->width($cfg['w'])
                ->height($cfg['h'])
                ->position($cfg['x'], $cfg['y'])
                ->frameless()
                ->hasShadow(true)
                ->resizable($cfg['resizable']);
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
