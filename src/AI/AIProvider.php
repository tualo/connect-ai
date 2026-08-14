<?php

namespace Tualo\Connect\AI;

interface AIProvider
{
    public function getName(): string;

    public function chat(array $messages, array $options = []): AIResponse;

    public function stream(array $messages, array $options = []): \Generator;

    public function setOptions(array $options): self;

    public function setApiKey(string $key): self;

    public function getApiKey(): ?string;

    public function setModel(string $model): self;

    public function getModel(): string;
}
