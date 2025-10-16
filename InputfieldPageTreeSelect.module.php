<?php namespace ProcessWire;

class InputfieldPageTreeSelect extends Inputfield implements InputfieldPageListSelection {

	protected $parentSorts = [];

	/**
	 * Init
	 */
	public function init() {
		$this->parent_id = 0;
		$this->limit = 5000;
		parent::init();
	}

	/**
	 * Render ready
	 */
	public function renderReady(?Inputfield $parent = null, $renderValueMode = false) {
		$settings = [
			'selectLabel' => $this->_('Select'),
			'unselectLabel' => $this->_('Unselect'),
		];
		$this->wire()->config->js('InputfieldPageTreeSelect', $settings);
		return parent::renderReady($parent, $renderValueMode);
	}

	/**
	 * Render
	 */
	public function ___render() {
		$config = $this->wire()->config;
		$pages = $this->wire()->pages;
		$rootId = $this->parent_id ?: 1;

		// Input attributes
		$this->addClass('ipts-input');
		$attrs = $this->getAttributes();
		$attrsString = $this->getAttributesString($attrs);

		// Labels
		$labels = [
			'change' => $this->_('Change'),
			'cancel' => $this->_('Cancel'),
			'clear' => $this->_('Clear'),
			'restore' => $this->_('Restore'),
			'scroll' => $this->_('Scroll to selected'),
			'filter' => $this->_('Filter...'),
			'noMatch' => $this->_('No matching items'),
		];

		// Selector
		$selector = "(id=$rootId), (has_parent=$rootId), limit=$this->limit, sort=sort, include=all";
		// Exclude ProcessPageList hidden pages unless user is superuser
		$pplConfig = $this->wire()->modules->getConfig('ProcessPageList');
		if(!empty($pplConfig['hidePages']) && !$this->wire()->user->isSuperuser()) {
			$selector .= ", id!=" . implode('|', $pplConfig['hidePages']);
		}
		// Exclude admin pages option
		if($this->excludeAdminPages) $selector .= ", template!=admin, has_parent!=$config->adminRootPageID";
		// Exclude by template option
		if($this->excludeTemplates) {
			$templatesStr = implode("|", $this->excludeTemplates);
			$selector .= ", template!=$templatesStr";
		}
		// Fields
		$fields = ['title', 'name', 'parent_id', 'status'];

		// Work out parent sorts
		$parentIds = implode(',', $pages->findIDs($selector . ', children.count>0'));
		$sql = <<<EOT
SELECT pages.id, pages.templates_id, pages_sortfields.sortfield AS page_sort FROM pages 
LEFT JOIN pages_sortfields ON pages.id = pages_sortfields.pages_id 
WHERE pages.id IN ($parentIds) 
EOT;
		$query = $this->wire()->database->query($sql);
		$parents = $query->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_ASSOC);
		foreach($parents as $key => $value) {
			// Get the parent's template
			$template = $this->wire()->templates->get($value[0]['templates_id']);
			// Template sortfield takes precedence
			$sortfield = $template->sortfield ?: $pages->sortfields()->decode($value[0]['page_sort']);
			// Skip over values of 'sort' because this is the default
			if($sortfield === 'sort') continue;
			// Set the sortfield to parentSorts array
			$this->parentSorts[$key] = $sortfield;
			// Add sortfield to fields without any prefix
			$fields[] = ltrim($sortfield, '-');
		}
		$fields = array_unique($fields);

		// Get raw data
		$data = $this->getRawData($selector, $fields);
		// The root page must be in the returned data or else the menu cannot be built
		if(!isset($data[$rootId])) {
			throw new WireException('Root page was not found in returned data. Check that any supplied selector option does not exclude the root page.');
		}
		$currentLabel = $data[$this->value]['title'] ?? '';
		$currentLabel = $this->wire()->sanitizer->entities($currentLabel);

		// Build tree from data
		$tree = $this->buildTree($data, $rootId);

		// Get tree markup
		$treeMarkup = $this->renderTree($tree);

		// Return markup
		// For some reason the position of the filter input relative to the hidden inputfield input
		// can have a significant impact on Interaction to Next Paint (INP) time when focusing the filter input.
		return <<<EOT
<div class="ipts-outer">
	<div class="ipts-input-controls" data-previous="" data-current="$this->value">
		<div class="current-label">$currentLabel</div>
		<button type="button" class="ipts-change"><i class="fa fa-plus-circle"></i> <span>{$labels['change']}</span></button>
		<button type="button" class="ipts-cancel"><i class="fa fa-times-circle"></i> <span>{$labels['cancel']}</span></button>
		<button type="button" class="ipts-clear"><i class="fa fa-minus-circle"></i> <span>{$labels['clear']}</span></button>
		<button type="button" class="ipts-restore"><i class="fa fa-undo"></i> <span>{$labels['restore']}</span></button>
		<button type="button" class="ipts-scroll"><i class="fa fa-arrow-circle-down"></i> <span>{$labels['scroll']}</span></button>
	</div>
	<input type="text" class="uk-input ipts-filter InputfieldIgnoreChanges" placeholder="{$labels['filter']}">
	<input type="text" $attrsString>
	<div class="ipts-tree">
		$treeMarkup
		<div class="ipts-no-match">{$labels['noMatch']}</div>
	</div>
</div>
EOT;
	}

	/**
	 * Render tree
	 *
	 * @param array $items
	 * @return string
	 */
	protected function renderTree($items) {
		if(!$items) return '';
		$sanitizer = $this->wire()->sanitizer;
		$out = "<div class='ipts-list'>";
		foreach($items as $id => $item) {
			$class = 'ipts-item';
			if($item['status'] & Page::statusHidden) $class .= ' status-hidden';
			if($item['status'] & Page::statusUnpublished) $class .= ' status-unpublished';
			$childrenCount = '';
			$after = '';
			if($item['children']) {
				$class .= ' has-children';
				$count = count($item['children']);
				$childrenCount = " <span class='children-count'>$count</span>";
				$after .= $this->renderTree($item['children']);
			}
			$label = $sanitizer->entities($item['title']);
			$out .= "<div class='$class' data-id='$id'><span class='label'>$label</span>$childrenCount</div>$after";
		}
		$out .= "</div>";
		return $out;

	}

	/**
	 * Build a nested tree of page data
	 * Adapted from here: https://stackoverflow.com/a/27360654/1036672
	 *
	 * @param array $data
	 * @return mixed
	 */
	protected function buildTree($data, $rootId) {
		$grouped = [];
		foreach($data as $id => $item) {
			// Fall back to page name if page title is empty
			if(empty($item['title'])) $item['title'] = $item['name'];
			$grouped[$item['parent_id']][$id] = $item;
		}
		$fnBuilder = function($siblings) use (&$fnBuilder, $grouped) {
			foreach($siblings as $id => $sibling) {
				if(isset($grouped[$id])) {
					$sibling['children'] = $fnBuilder($grouped[$id]);
					// Sort the children if the parent has a sortfield
					if(isset($this->parentSorts[$id])) {
						$sortfield = $this->parentSorts[$id];
						$sortorder = $sortfield[0] === '-' ? SORT_DESC : SORT_ASC;
						$sortfield = ltrim($sortfield, '-');
						// Preserve keys to apply back later
						$keys = array_keys($sibling['children']);
						// Natural, case-insensitive sort by sortfield - does not preserve keys
						array_multisort(array_map(function($element) use ($sortfield, $sortorder) {
							return $element[$sortfield];
						}, $sibling['children']), $sortorder, SORT_NATURAL | SORT_FLAG_CASE, $sibling['children'], $keys);
						// Apply keys
						$sibling['children'] = array_combine($keys, $sibling['children']);
					}
				} else {
					$sibling['children'] = [];
				}
				$siblings[$id] = $sibling;
			}
			return $siblings;
		};
		return $fnBuilder($grouped[$data[$rootId]['parent_id']]);
	}

	/**
	 * Get raw data for the tree
	 *
	 * @param string $selector
	 * @param array $fields
	 * @return array
	 */
	public function ___getRawData($selector, $fields) {
		$pages = $this->wire()->pages;
		$count = $pages->count($selector);
		if($count > $this->limit) {
			$this->wire()->warning($this->_("Page count exceeds configured limit for InputfieldPageTreeSelect. Please increase the limit or uninstall the module."));
		}
		return $pages->findRaw($selector, $fields, ['nulls' => true, 'flat' => true]);
	}

	/**
	 * Config inputfields
	 */
	public function ___getConfigInputfields() {
		$inputfields = parent::___getConfigInputfields();
		$modules = $this->wire()->modules;

		/** @var InputfieldCheckbox $f */
		$f = $modules->get('InputfieldCheckbox');
		$name = 'excludeAdminPages';
		$f->name = $name;
		$f->label = $this->_('Exclude admin pages');
		$f->checked = $this->$name === 1 ? 'checked' : '';
		$inputfields->add($f);

		/** @var InputfieldAsmSelect $f */
		$f = $modules->get('InputfieldAsmSelect');
		$name = 'excludeTemplates';
		$f->name = $name;
		$f->label = $this->_('Exclude pages by template');
		foreach($this->wire()->templates as $template) {
			$f->addOption($template->id, $template->get('label|name'));
		}
		$f->value = $this->$name;
		$inputfields->add($f);

		/** @var InputfieldInteger $f */
		$f = $modules->get('InputfieldInteger');
		$name = 'limit';
		$f->name = $name;
		$f->label = $this->_('Limit for total pages in tree');
		$f->inputType = 'number';
		$f->value = $this->$name;
		$inputfields->add($f);

		return $inputfields;
	}


	/**
	 * Install
	 * Add module as a selection inputfield to InputfieldPage
	 */
	public function ___install() {
		$data = $this->wire()->modules->getConfig('InputfieldPage');
		$data['inputfieldClasses'][] = $this->className();
		$this->wire()->modules->saveConfig('InputfieldPage', $data);
	}

	/**
	 * Uninstall
	 * Remove module as a selection inputfield from InputfieldPage
	 */
	public function ___uninstall() {
		$data = $this->wire()->modules->getConfig('InputfieldPage');
		foreach($data['inputfieldClasses'] as $key => $value) {
			if($value == $this->className()) unset($data['inputfieldClasses'][$key]);
		}
		$this->wire()->modules->saveConfig('InputfieldPage', $data);
	}

}
