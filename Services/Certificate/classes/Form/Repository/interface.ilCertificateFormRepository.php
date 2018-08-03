<?php


interface ilCertificateFormRepository
{
	/**
	 * @param ilCertificateGUI $certificateGUI
	 * @param ilCertificate $certificateObject
	 * @return ilPropertyFormGUI
	 */
	public function createForm(ilCertificateGUI $certificateGUI, ilCertificate $certificateObject);

	/**
	 * @param array $formFields
	 * @return mixed
	 */
	public function save(array $formFields);

	/**
	 * @param $content
	 * @return mixed
	 */
	public function fetchFormFieldData($content);
}
