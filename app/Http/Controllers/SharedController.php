<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePublicLinkRequest;
use App\Http\Requests\ShareToUserRequest;
use App\Models\File;
use App\Models\FilePublicLink;
use App\Models\FileShare;
use App\Models\Folder;
use App\Models\User;
use App\Services\FileService;
use App\Services\ShareService;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SharedController extends Controller
{
    public function __construct(
        private readonly ShareService $shareService,
        private readonly FileService $fileService
    ) {
    }

    public function index(): View
    {
        $user = auth()->user();

        $sharedWithMe = $this->fileService->listSharedWith($user);
        $myShares = $this->fileService->listOwnedShares($user);
        $publicLinks = $this->shareService->linksForOwner($user);
        $users = User::query()->whereKeyNot($user->id)->orderBy('name')->get();
        $myFiles = File::query()->where('user_id', $user->id)->latest()->get(['id', 'name']);
        $myFolders = Folder::query()->where('user_id', $user->id)->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);

        return view('shared.index', compact('sharedWithMe', 'myShares', 'publicLinks', 'users', 'myFiles', 'myFolders'));
    }

    public function shareToUser(ShareToUserRequest $request): RedirectResponse
    {
        [$type, $id] = explode(':', (string) $request->input('shared_item'));
        $permission = (string) $request->input('permission');
        $targetUserId = (int) $request->input('target_user_id');

        if ($type === 'file') {
            $file = File::query()->findOrFail((int) $id);
            $this->authorize('update', $file);

            $this->shareService->shareToUser($file, $request->user(), $targetUserId, $permission);

            return back()->with('status', 'File shared successfully.');
        }

        $folder = Folder::query()->findOrFail((int) $id);
        $this->authorize('update', $folder);
        $files = $this->collectFolderFiles($folder);

        foreach ($files as $file) {
            $this->shareService->shareToUser($file, $request->user(), $targetUserId, $permission);
        }

        return back()->with('status', 'Folder shared successfully ('.$files->count().' files).');
    }

    public function revokeShare(FileShare $share): RedirectResponse
    {
        $this->authorize('update', $share->file);
        $this->shareService->revokeUserShare($share->file, $share->target_user_id);

        return back()->with('status', 'Share revoked.');
    }

    public function createPublicLink(CreatePublicLinkRequest $request): RedirectResponse
    {
        [$type, $id] = explode(':', (string) $request->input('shared_item'));
        $password = $request->string('password')->toString() ?: null;
        $expiresAt = $request->string('expires_at')->toString() ?: null;

        if ($type === 'file') {
            $file = File::query()->findOrFail((int) $id);
            $this->authorize('view', $file);
            $this->shareService->createPublicLink($file, $request->user(), $password, $expiresAt);

            return back()->with('status', 'Public link created.');
        }

        $folder = Folder::query()->findOrFail((int) $id);
        $this->authorize('view', $folder);
        $files = $this->collectFolderFiles($folder);

        foreach ($files as $file) {
            $this->shareService->createPublicLink($file, $request->user(), $password, $expiresAt);
        }

        return back()->with('status', 'Public links created for folder ('.$files->count().' files).');
    }

    public function revokePublicLink(FilePublicLink $link): RedirectResponse
    {
        $this->authorize('view', $link->file);
        $this->shareService->revokePublicLink($link);

        return back()->with('status', 'Public link revoked.');
    }

    public function sendPublicLinkEmail(FilePublicLink $link): RedirectResponse
    {
        $this->authorize('view', $link->file);

        request()->validate([
            'recipient_email' => ['required', 'email'],
        ]);

        $smtp = app(\App\Services\ControlCenterService::class)->smtpConfig();
        if (empty($smtp['host']) || empty($smtp['from_email'])) {
            return back()->withErrors(['smtp' => 'SMTP is not configured in Control Center.']);
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $smtp['host'],
            'mail.mailers.smtp.port' => $smtp['port'],
            'mail.mailers.smtp.encryption' => $smtp['encryption'],
            'mail.mailers.smtp.username' => $smtp['username'],
            'mail.mailers.smtp.password' => $smtp['password'],
            'mail.from.address' => $smtp['from_email'],
            'mail.from.name' => $smtp['from_name'],
        ]);

        $linkUrl = route('public-share.download', $link->token);
        try {
            Mail::raw("AkenFile shared link:\n\n{$linkUrl}", function ($message) use ($link): void {
                $message->to((string) request('recipient_email'))
                    ->subject('Shared file: '.$link->file?->name);
            });
        } catch (\Throwable $exception) {
            return back()->withErrors(['smtp' => 'Failed sending email: '.$exception->getMessage()]);
        }

        return back()->with('status', 'Share link sent via email.');
    }

    private function collectFolderFiles(Folder $folder): Collection
    {
        $folder->loadMissing(['files', 'children.files', 'children.children']);

        $files = collect($folder->files);
        foreach ($folder->children as $child) {
            $files = $files->merge($this->collectFolderFiles($child));
        }

        return $files->unique('id')->values();
    }
}
