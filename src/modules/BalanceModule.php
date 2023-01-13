<?php

namespace hiapi\heppy\modules;

class BalanceModule extends AbstractModule
{
    /** {@inheritdoc} */
    public array $uris = [
        'balance' => 'http://hostmaster.ua/epp/balance-1.0',
    ];

    public ?string $object = 'balance';

    public function balanceInfo(array $row = []) : array
    {
        $contract = $this->tool->getContract();
        if (empty($contract)) {
            return [];
        }

        try {
            $info = $this->tool->commonRequest("{$this->object}:info", array_filter([
                'contract' => $contract,
            ], $this->getFilterCallback()), [
                'contract' => 'contract',
                'expiration_date' => 'contractUntil',
                'status' => 'status',
                'balance' => 'balance',
            ]);
        } catch (\Thowable $e) {
            return [];
        }

        return $this->buildPoll($info);
    }

    protected function buildPoll(array $data) : array
    {
        $minBalance = $this->tool->getMinBalance() ?? 0;
        if ($minBalance > $data['balance']) {
            return [];
        }

        return [
            'request_date' => date("Y-m-d H:i:s"),
            'type' => 'low_balance',
            'name' => $data['contract'],
            'action_client' => $this->tool->getRegistrar(),
        ];
    }
}
