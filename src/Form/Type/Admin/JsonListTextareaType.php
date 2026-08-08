<?php

declare(strict_types=1);

namespace App\Navigating\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class JsonListTextareaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static function (mixed $value): string {
                if ([] === $value || null === $value) {
                    return '[]';
                }

                if (!is_array($value) || !array_is_list($value)) {
                    throw new TransformationFailedException('Expected list data for JSON textarea.');
                }

                $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                if (!is_string($json)) {
                    throw new TransformationFailedException('Unable to encode JSON list value.');
                }

                return $json;
            },
            static function (mixed $value): array {
                if (null === $value || '' === trim((string) $value)) {
                    return [];
                }

                try {
                    $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new TransformationFailedException('Invalid JSON list.', 0, $exception);
                }

                if (!is_array($decoded) || !array_is_list($decoded)) {
                    throw new TransformationFailedException('JSON textarea value must decode to a list.');
                }

                foreach ($decoded as $token) {
                    if (!is_string($token)) {
                        throw new TransformationFailedException('JSON list values must be strings.');
                    }
                }

                /** @var list<string> $decoded */
                return $decoded;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'rows' => 5,
                'spellcheck' => 'false',
            ],
            'empty_data' => '[]',
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
