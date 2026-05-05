<?php

namespace App\Rules;

use App\Enums\SDGOptions;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Log;

class SDGTags implements DataAwareRule, ValidationRule, ValidatorAwareRule
{
    protected array $data = [];

    protected $validator;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validSDGs = collect(SDGOptions::cases())->map(fn ($case) => $case->value)->toArray();

        // Handle both single string (from nestedRecursiveRules) and array
        $tags = is_array($value) ? $value : [$value];
        $invalidTags = [];

        foreach ($tags as $tag) {
            Log::info("Validating SDG tag: {$tag}");
            if (! in_array($tag, $validSDGs)) {
                $invalidTags[] = $tag;
            }
        }

        Log::info('Validation completed. Invalid tags: '.implode(', ', $invalidTags));
        if (! empty($invalidTags)) {
            $fail('Only valid SDGs are accepted, invalid tag(s) detected: '.implode(', ', $invalidTags).'.');
        }
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function setValidator($validator): static
    {
        $this->validator = $validator;

        return $this;
    }
}
