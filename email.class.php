<?php namespace Pardot;

class Email extends API
{
	/**
	 * Retrieves email object by ID
	 *
	 * @param $id
	 * @return object
	 */
	public function getById($id) {
		// call API
		$email = $this->doOperationByIdOrEmail('email', 'read', $id);

		// return object or null
		if ($email['success']) {
			return $email['response']->email;
		} else {
			return null;
		}
	}
}