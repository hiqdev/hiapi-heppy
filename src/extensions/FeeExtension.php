<?php

namespace hiapi\heppy\extensions;


class FeeExtension
{
    public function apply(string $command, array $data): array
    {
        if ($this->isApplicable($command, $data)) {
            return $this->addFeeExt($command, $data);
        }

        return $data;
    }

    public function isApplicable(string $command, array $data): bool
    {
        if (!in_array($command, ['domain:check', 'domain:renew', 'domain:transfer'], true)) {
            return false;
        }

        if ($command === 'domain:transfer' && $data['op'] !== 'request') {
            return false;
        }

        return true;

    }

    public function addFeeExt(string $command, array $data): array
    {
        $extension = array_filter([
            'command' => 'fee:' . substr($command, 7),
            'name' => $data['name'] ?? reset($data['names']),
            'currency' => $this->tool->getCurrency ?? 'USD',
            'fee' => $data['fee'],
            'period' => $data['period'],
            'action' => $data['fee-action'] ?? ($command === 'domain:check' ? 'create' : substr($command, 7)),
        ]);
        $data['extensions'][] = $extension;

        return $data;
    }
}
