<?php
declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Get the default context variables for logging.
     *
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        return array_merge(parent::context(), [
            'chat_id' => 'bar',
        ]);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }


    public function report(Throwable $e)
    {
        $chatID = Env::get("NUTGRAM_LOG_CHAT_ID");
        if (!$chatID) {
            return;
        }

        /** @var Nutgram $bot */
        $bot = App::get(Nutgram::class);
        $data = [
            'description' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        $bot->sendMessage(text: (string)view('report', $data), chat_id: $chatID, parse_mode: 'html');
    }
}
