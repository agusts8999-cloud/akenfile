<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicShareController extends Controller
{
    public function __construct(
        private readonly ShareService $shareService,
        private readonly FileService $fileService
    ) {
    }

    public function download(string $token, Request $request)
    {
        $link = $this->shareService->resolvePublicLink($token);

        abort_if(! $link, Response::HTTP_NOT_FOUND);

        if ($link->password) {
            $providedPassword = $request->string('password')->toString();
            abort_unless($providedPassword && \Illuminate\Support\Facades\Hash::check($providedPassword, $link->password), Response::HTTP_FORBIDDEN);
        }

        return $this->fileService->download($link->file);
    }
}
