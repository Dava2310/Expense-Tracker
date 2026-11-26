<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Emails the owner of an account whose balance has just dropped below its minimum.
 */
class LowBalanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private bool $crossedBelowMinimum;

    private int $accountId;

    private string $accountName;

    private string $balance;

    private string $minimum;

    /**
     * Takes a snapshot of the account right after the money left it.
     */
    public function __construct(Account $account, string|float $amountTakenOut)
    {
        $balanceBefore = $account->balance_in_cents + (int) round((float) $amountTakenOut * 100);

        $this->crossedBelowMinimum = $account->isBelowMinimum() && $balanceBefore >= $account->min_balance_in_cents;
        $this->accountId = $account->id;
        $this->accountName = $account->name;
        $this->balance = $account->formatMoney($account->balance);
        $this->minimum = $account->formatMoney($account->min_balance);
    }

    /**
     * Sends the email only when the balance has just crossed below the minimum.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->crossedBelowMinimum ? ['mail'] : [];
    }

    /**
     * Builds the email with the account, its balance and its minimum.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low balance on '.$this->accountName)
            ->greeting('Hi '.Str::before($notifiable->name, ' ').',')
            ->line('**'.$this->accountName.'** has dropped below its minimum balance.')
            ->line('Balance: **'.$this->balance.'**')
            ->line('Minimum balance: **'.$this->minimum.'**')
            ->action('View account', route('accounts.show', $this->accountId));
    }
}
