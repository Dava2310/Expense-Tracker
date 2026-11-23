<?php

use App\Models\Expense;

test('an expense amount is stored as whole cents and read back as a decimal', function () {
    $expense = Expense::factory()->create(['amount' => 84.30])->fresh();

    expect($expense->amount_in_cents)->toBe(8430)
        ->and($expense->amount)->toBe(84.30);
});

test('expense amounts that floats cannot hold exactly are stored to the cent', function (string|float $amount, int $cents) {
    $expense = Expense::factory()->create(['amount' => $amount])->fresh();

    expect($expense->amount_in_cents)->toBe($cents);
})->with([
    'a tenth' => [0.1, 10],
    'a string from a form' => ['0.30', 30],
    'the input maximum' => ['999999.99', 99999999],
]);
