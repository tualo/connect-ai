<?php

namespace Tualo\Connect\AI;

class AIPrompt
{
    /** @var array */
    private array $messages = [];

    public function __construct(?string $systemMessage = null)
       {
        if ($systemMessage) {
             $this->messages[] = ['role' => 'system', 'content' => $systemMessage];
           }
       }

    public function addMessage(string $role, string $content): self
       {
         $this->messages[] = ['role' => $role, 'content' => $content];
        return $this;
       }

    public function addUser(string $content): self
       {
        return $this->addMessage('user', $content);
       }

    public function addAssistant(string $content): self
       {
        return $this->addMessage('assistant', $content);
       }

    public function addSystem(string $content): self
       {
        return $this->addMessage('system', $content);
       }

    public function addUserWithImages(string $content, array $images): self
       {
        return $this->addMessage('user', $content, $images);
       }

    public function build(): array
       {
        return $this->messages;
       }

    public static function fromMessages(array $messages): self
       {
        $prompt = new self();
         $prompt->messages = $messages;
        return $prompt;
       }

    public static function fromData(string $data, string $instruction, string $context = ''): self
       {
         $system = $context ? "You are a helpful assistant specializing in analyzing business data. $context" : "You are a helpful assistant specializing in analyzing data.";
         $prompt = new self($system);
         $prompt->addUser("$instruction\n\n$data");
        return $prompt;
       }

    public function merge(self $other): self
       {
         $this->messages = array_merge($this->messages, $other->messages);
        return $this;
       }

    public function lastMessage(): ?array
       {
        return $this->messages[array_key_last($this->messages)] ?? null;
       }

    public function count(): int
       {
        return count($this->messages);
       }

    public function clear(): self
       {
         $this->messages = [];
        return $this;
       }

    public function isEmpty(): bool
       {
        return empty($this->messages);
       }
}
