<?php
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc. 
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

/**
 *Staff management.
 */

namespace Box\Mod\Staff\Api;

class Admin extends \Api_Abstract
{
    /**
     * Get paginated list of staff members.
     *
     * @return array
     */
    public function get_list($data)
    {
        $data['no_cron'] = true;
        [$sql, $params] = $this->getService()->getSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getPer_page();
        $pager = $this->di['pager']->getSimpleResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $staff = $this->di['db']->getExistingModelById('Admin', $item['id'], 'Admin is not found');
            $pager['list'][$key] = $this->getService()->toModel_AdminApiArray($staff);
        }

        return $pager;
    }

    /**
     * Get staff member by id.
     *
     * @param int $id - staff member ID
     *
     * @return array
     *
     * @throws \Box_Exception
     */
    public function get($data)
    {
        $required = [
            'id' => 'ID is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');

        return $this->getService()->toModel_AdminApiArray($model);
    }

    /**
     * Update staff member.
     *
     * @param int $id - staff member ID
     *
     * @optional string $email - new email
     * @optional string $name - new name
     * @optional string $status - new status
     * @optional string $signature - new signature
     * @optional int $admin_group_id - new group id
     *
     * @return bool
     *
     * @throws \Box_Exception
     */
    public function update($data)
    {
        $required = [
            'id' => 'ID is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        if (!is_null($data['email'])) {
            $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);
        }

        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');

        return $this->getService()->update($model, $data);
    }

    /**
     * Completely delete staff member. Removes all related activity from logs.
     *
     * @param int $id - staff member ID
     *
     * @return bool
     *
     * @throws \Box_Exception
     */
    public function delete($data)
    {
        $required = [
            'id' => 'ID is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');

        return $this->getService()->delete($model);
    }

    /**
     * Change staff member password.
     *
     * @param int    $id               - staff member ID
     * @param string $password         - new staff member password
     * @param string $password_confirm - repeat new staff member password
     *
     * @return bool
     *
     * @throws \Box_Exception
     */
    public function change_password($data)
    {
        $required = [
            'id' => 'ID is missing',
            'password' => 'Password required',
            'password_confirm' => 'Password confirmation required',
        ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);

        if ($data['password'] != $data['password_confirm']) {
            throw new \Box_Exception('Passwords do not match');
        }

        $validator->isPasswordStrong($data['password']);

        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');

        return $this->getService()->changePassword($model, $data['password']);
    }

    /**
     * Create new staff member.
     *
     * @param string $email          - email of new staff member
     * @param string $password       - password of new staff member
     * @param string $name           - name of new staff member
     * @param string $admin_group_id - admin group id of new staff member
     *
     * @optional string $signature - signature of new staff member
     *
     * @return int - ID of newly created staff member
     *
     * @throws \Box_Exception
     */
    public function create($data)
    {
        $required = [
            'email' => 'Email param is missing',
            'password' => 'Password param is missing',
            'name' => 'Name param is missing',
            'admin_group_id' => 'Group id is missing',
        ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);

        $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);

        $validator->isPasswordStrong($data['password']);

        return $this->getService()->create($data);
    }

    /**
     * Return staff member permissions.
     *
     * @param int $id - staff member id
     *
     * @return array
     */
    public function permissions_get($data)
    {
        $required = [
            'id' => 'ID is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');

        return $this->getService()->getPermissions($model->id);
    }

    /**
     * Update staff member permissions.
     *
     * @param int   $id          - staff member id
     * @param array $permissions - staff member permissions
     *
     * @return bool
     */
    public function permissions_update($data)
    {
        $required = [
            'id' => 'ID is missing',
            'permissions' => 'Permissions parameter missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');

        $this->getService()->setPermissions($model->id, $data['permissions']);

        $this->di['logger']->info('Changed staff member %s permissions', $model->id);

        return true;
    }

    /**
     * Return pairs of staff member groups.
     *
     * @return type
     */
    public function group_get_pairs($data)
    {
        return $this->getService()->getAdminGroupPair();
    }

    /**
     * Return paginate list of staff members groups.
     *
     * @return array
     */
    public function group_get_list($data)
    {
        [$sql, $params] = $this->getService()->getAdminGroupSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getPer_page();
        $pager = $this->di['pager']->getSimpleResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $model = $this->di['db']->getExistingModelById('AdminGroup', $item['id'], 'Post not found');
            $pager['list'][$key] = $this->getService()->toAdminGroupApiArray($model, false, $this->getIdentity());
        }

        return $pager;
    }

    /**
     * Create new staff members group.
     *
     * @param string $name - name of staff members group
     *
     * @return int - new staff group ID
     *
     * @throws \Box_Exception
     */
    public function group_create($data)
    {
        $required = [
            'name' => 'Staff group is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->createGroup($data['name']);
    }

    /**
     * Return staff group details.
     *
     * @param int $id - group id
     *
     * @return array - group details
     *
     * @throws \Box_Exception
     */
    public function group_get($data)
    {
        $required = [
            'id' => 'Group id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('AdminGroup', $data['id'], 'Group not found');

        return $this->getService()->toAdminGroupApiArray($model, true, $this->getIdentity());
    }

    /**
     * Remove staff group.
     *
     * @param int $id - group id
     *
     * @return bool
     *
     * @throws \Box_Exception
     */
    public function group_delete($data)
    {
        $required = [
            'id' => 'Group id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('AdminGroup', $data['id'], 'Group not found');

        return $this->getService()->deleteGroup($model);
    }

    /**
     * Update staff group.
     *
     * @param int $id - group id
     *
     * @optional int $name - new group name
     *
     * @return bool
     *
     * @throws \Box_Exception
     */
    public function group_update($data)
    {
        $required = [
            'id' => 'Group id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('AdminGroup', $data['id'], 'Group not found');

        return $this->getService()->updateGroup($model, $data);
    }

    /**
     * Get paginated list of staff logins history.
     *
     * @return array
     */
    public function login_history_get_list($data)
    {
        [$sql, $params] = $this->getService()->getActivityAdminHistorySearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getPer_page();
        $pager = $this->di['pager']->getSimpleResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $activity = $this->di['db']->getExistingModelById('ActivityAdminHistory', $item['id'], sprintf('Staff activity item #%s not found', $item['id']));
            if ($activity) {
                $pager['list'][$key] = $this->getService()->toActivityAdminHistoryApiArray($activity);
            }
        }

        return $pager;
    }

    /**
     * Get details of login history event.
     *
     * @param int $id - event id
     *
     * @return array
     *
     * @throws ErrorException
     */
    public function login_history_get($data)
    {
        $required = [
            'id' => 'Id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('ActivityAdminHistory', $data['id'], 'Event not found');

        return $this->getService()->toActivityAdminHistoryApiArray($model);
    }

    /**
     * Delete login history event.
     *
     * @param int $id - event id
     *
     * @return bool
     *
     * @throws ErrorException
     */
    public function login_history_delete($data)
    {
        $required = [
            'id' => 'Id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('ActivityAdminHistory', $data['id'], 'Event not found');

        return $this->getService()->deleteLoginHistory($model);
    }

    /**
     * Deletes admin login logs with given IDs.
     *
     * @param array $ids - IDs for deletion
     *
     * @return bool
     */
    public function batch_delete_logs($data)
    {
        $required = [
            'ids' => 'IDs not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        foreach ($data['ids'] as $id) {
            $this->login_history_delete(['id' => $id]);
        }

        return true;
    }
}
