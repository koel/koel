<?php

namespace App\Services;

use App\Exceptions\InvitationNotFoundException;
use App\Mail\UserInvite;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserInvitationService
{
    public function __construct(private Hash $hash)
    {
    }

    /** @return Collection|array<array-key, User> */
    public function invite(array $emails, bool $isAdmin, User $invitor): Collection
    {
        return DB::transaction(function () use ($emails, $isAdmin, $invitor) {
            return collect($emails)->map(fn ($email) => $this->inviteOne($email, $isAdmin, $invitor));
        });
    }

    /** @throws InvitationNotFoundException */
    public function getUserProspectByToken(string $token): User
    {
        return User::query()->where('invitation_token', $token)->firstOr(static function (): void {
            throw new InvitationNotFoundException();
        });
    }

    /** @throws InvitationNotFoundException */
    public function revokeByEmail(string $email): void
    {
        /** @var ?User $user */
        $user = User::query()->where('email', $email)->first();
        throw_unless($user?->is_prospect, new InvitationNotFoundException());
        $user->delete();
    }

    private function inviteOne(string $email, bool $isAdmin, User $invitor): User
    {
        /** @var User $invitee */
        $invitee = User::query()->create([
            'name' => '',
            'email' => $email,
            'password' => '',
            'is_admin' => $isAdmin,
            'invited_by_id' => $invitor->id,
            'invitation_token' => Str::uuid()->toString(),
            'invited_at' => now(),
        ]);

        Mail::to($email)->queue(new UserInvite($invitee));

        return $invitee;
    }

    /** @throws InvitationNotFoundException */
    public function accept(string $token, string $name, string $password): User
    {
        $user = $this->getUserProspectByToken($token);

        $user->update([
            'name' => $name,
            'password' => $this->hash->make($password),
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
        ]);

        return $user;
    }
}
