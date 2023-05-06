<?php
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc. 
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Client;

use \FOSSBilling\InjectionAwareInterface;

class ServiceBalance implements InjectionAwareInterface
{
    protected ?\Pimple\Container $di;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function getClientBalance(\Model_Client $c)
    {
        return (float) $this->clientTotal($c);
    }

    public function clientTotal(\Model_Client $c)
    {
        $sql = '
        SELECT SUM(amount) as client_total
        FROM client_balance
        WHERE client_id = ?
        GROUP BY client_id
        ';

        return $this->di['db']->getCell($sql, [$c->id]);
    }

    public function rmByClient(\Model_Client $client)
    {
        $clientBalances = $this->di['db']->find('ClientBalance', 'client_id = ?', [$client->id]);
        foreach ($clientBalances as $balanceModel) {
            $this->di['db']->trash($balanceModel);
        }
    }

    public function rm(\Model_ClientBalance $model)
    {
        $this->di['db']->trash($model);
    }

    public function toApiArray(\Model_ClientBalance $model)
    {
        $client = $this->di['db']->getExistingModelById('Client', $model->client_id, 'Client not found');

        return [
            'id' => $model->id,
            'description' => $model->description,
            'amount' => $model->amount,
            'currency' => $client->currency,
            'created_at' => $model->created_at,
        ];
    }

    public function getSearchQuery($data)
    {
        $q = 'SELECT m.*, c.currency  as currency
              FROM client_balance as m
                LEFT JOIN client as c on c.id = m.client_id';

        $id = $data['id'] ?? null;
        $client_id = $data['client_id'] ?? null;
        $date_from = $data['date_from'] ?? null;
        $date_to = $data['date_to'] ?? null;

        $where = [];
        $params = [];

        if (null !== $id) {
            $where[] = 'm.id = :id';
            $params[':id'] = $id;
        }

        if (null !== $client_id) {
            $where[] = 'm.client_id = :client_id';
            $params[':client_id'] = $client_id;
        }

        if (null !== $date_from) {
            $where[] = 'm.created_at >= :date_from';
            $params[':date_from'] = strtotime($date_from);
        }

        if (null !== $date_to) {
            $where[] = 'm.created_at <= :date_to';
            $params[':date_to'] = strtotime($date_to);
        }

        if (!empty($where)) {
            $q .= ' WHERE ' . implode(' AND ', $where);
        }
        $q .= ' ORDER by m.id DESC';

        return [$q, $params];
    }

    /**
     * @param float  $amount
     * @param string $description
     * @param array  $data
     *
     * @return \Model_ClientBalance
     *
     * @throws \Box_Exception
     */
    public function deductFunds(\Model_Client $client, $amount, $description, array $data = null)
    {
        if (!is_numeric($amount)) {
            throw new \Box_Exception('Funds amount is not valid');
        }

        if (0 == strlen(trim($description))) {
            throw new \Box_Exception('Funds description is not valid');
        }

        $credit = $this->di['db']->dispense('ClientBalance');
        $credit->client_id = $client->id;
        $credit->type = $data['type'] ?? 'default';
        $credit->rel_id = $data['rel_id'] ?? null;
        $credit->description = $description;
        $credit->amount = -$amount;
        $credit->created_at = date('Y-m-d H:i:s');
        $credit->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($credit);

        return $credit;
    }
}
