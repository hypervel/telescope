<?php

declare(strict_types=1);

namespace LaravelHyperf\Telescope\Http\Controllers;

use LaravelHyperf\Telescope\Contracts\EntriesRepository;

class MailEmlController
{
    /**
     * Download the Eml content of the email.
     */
    public function show(EntriesRepository $storage, string $id): mixed
    {
        return response($storage->find($id)->content['raw'], 200, [
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="mail-' . $id . '.eml"',
        ]);
    }
}
