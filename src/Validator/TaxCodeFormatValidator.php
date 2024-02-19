<?php

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TaxCodeFormatValidator extends ConstraintValidator
{
    final public const TAX_CODE_TEMPLATES = [
        'DE' => 'DEXXXXXXXXX',
        'IT' => 'ITXXXXXXXXXXX',
        'GR' => 'GRXXXXXXXXX',
        'FR' => 'FRYYXXXXXXXXXX'
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TaxCodeFormat) {
            throw new UnexpectedTypeException($constraint, TaxCodeFormat::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!preg_match($this->buildTaxCodeRegex(), $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }

    /**
     * This method allows you to add any Tax code for any country by
     * adding it in the public constant "TAX_CODE_TEMPLATES".
     *
     * You have to be aware about the characters used for build the codes templates:
     *
     * - The first 2 chars will be "the country code" (that's why "TAX_CODES_TEMPLATES is an associative array
     *   where the keys are the country code).
     *
     * - The char 'Y' will represent any letter.
     * - The char 'X' will represent any digit.
     *
     * As an example, let's assume we want to add a Tax code for Spain:
     *
     *   final public const TAX_CODE_TEMPLATES = [
     *      ...
     *      'ES' => 'ESYYYYXXXXXX', // 'ES' followed by 4 letters and 6 digits.
     *      ...
     *   ];
     *
     * in this case, the regex segment that the method builds is:
     *
     *  'ES[A-Z]{4}\d{6}'
     *
     * @return string
     */
    public function buildTaxCodeRegex(): string
    {
        $formatRegex = [];

        foreach (self::TAX_CODE_TEMPLATES as $countryCode => $taxTemplate) {
            $regex = $countryCode;
            $regex .= sprintf('[A-Z]{%d}', substr_count($taxTemplate, 'Y'));
            $regex .= sprintf('\d{%d}', substr_count($taxTemplate, 'X'));

            $formatRegex[] = $regex;
        }

        return sprintf(
            '/^(%s)$/',
            join('|', $formatRegex)
        );
    }
}