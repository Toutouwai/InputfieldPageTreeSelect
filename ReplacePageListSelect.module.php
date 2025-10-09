<?php namespace ProcessWire;

class ReplacePageListSelect extends WireData implements Module {

	/**
	 * Ready
	 */
	public function ready() {
		$this->addHookBefore('InputfieldWrapper::renderInputfield', $this, 'replacePageListSelect');
	}

	/**
	 * Replace InputfieldPageListSelect with InputfieldPageTreeSelect
	 *
	 * @param HookEvent $event
	 */
	protected function replacePageListSelect(HookEvent $event) {
		$inputfield = $event->arguments(0);

		// Return early if inputfield is not InputfieldPageListSelect
		if($inputfield->className !== 'InputfieldPageListSelect') return;

		// Return early if hookable method returns false
		if(!$this->allowReplacement($inputfield)) return;

		// Get InputfieldPageTreeSelect via hookable method
		$ipts = $this->getPageTreeSelect($inputfield);
		foreach($inputfield->getArray() as $key => $value) {
			$ipts->set($key, $value);
		}
		$ipts->setAttributes($inputfield->getAttributes());
		$event->arguments(0, $ipts);
	}

	/**
	 * Allow replacement of this instance of InputfieldPageListSelect?
	 *
	 * @param InputfieldPageListSelect $inputfield
	 * @return bool
	 */
	public function ___allowReplacement(InputfieldPageListSelect $inputfield) {
		return true;
	}

	/**
	 * Get InputfieldPageTreeSelect
	 *
	 * @param InputfieldPageListSelect $inputfield
	 * @return InputfieldPageTreeSelect
	 */
	public function ___getPageTreeSelect(InputfieldPageListSelect $inputfield) {
		return $this->wire()->modules->get('InputfieldPageTreeSelect');
	}

}
