<?php
/**
 * Copyright (C) 2013-2021 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */

namespace Combodo\iTop\Application\UI\Base\Component\Panel;


use Combodo\iTop\Application\UI\Base\Component\Html\Html;
use Combodo\iTop\Application\UI\Base\iUIBlock;
use Combodo\iTop\Application\UI\Base\Layout\iUIContentBlock;
use Combodo\iTop\Application\UI\Base\Layout\UIContentBlock;
use Combodo\iTop\Application\UI\Base\tUIContentAreas;
use MetaModel;
use ormStyle;
use utils;

/**
 * Class Panel
 *
 * @author Stephen Abello <stephen.abello@combodo.com>
 * @package Combodo\iTop\Application\UI\Base\Component\Panel
 * @since 3.0.0
 */
class Panel extends UIContentBlock
{
	use tUIContentAreas;

	// Overloaded constants
	public const BLOCK_CODE = 'ibo-panel';
	public const DEFAULT_HTML_TEMPLATE_REL_PATH = 'base/components/panel/layout';
	public const DEFAULT_JS_TEMPLATE_REL_PATH = 'base/components/panel/layout';

	// Specific constants
	/** @var string ENUM_COLOR_PRIMARY */
	public const ENUM_COLOR_PRIMARY = 'primary';
	/** @var string ENUM_COLOR_SECONDARY */
	public const ENUM_COLOR_SECONDARY = 'secondary';

	/** @var string ENUM_COLOR_NEUTRAL */
	public const ENUM_COLOR_NEUTRAL = 'neutral';
	/** @var string ENUM_COLOR_INFORMATION */
	public const ENUM_COLOR_INFORMATION = 'information';
	/** @var string ENUM_COLOR_SUCCESS */
	public const ENUM_COLOR_SUCCESS = 'success';
	/** @var string ENUM_COLOR_FAILURE */
	public const ENUM_COLOR_FAILURE = 'failure';
	/** @var string ENUM_COLOR_WARNING */
	public const ENUM_COLOR_WARNING = 'warning';
	/** @var string ENUM_COLOR_DANGER */
	public const ENUM_COLOR_DANGER = 'danger';

	/** @var string ENUM_COLOR_GREY */
	public const ENUM_COLOR_GREY = 'grey';
	/** @var string ENUM_COLOR_BLUEGREY */
	public const ENUM_COLOR_BLUEGREY = 'blue-grey';
	/** @var string ENUM_COLOR_BLUE */
	public const ENUM_COLOR_BLUE = 'blue';
	/** @var string ENUM_COLOR_CYAN */
	public const ENUM_COLOR_CYAN = 'cyan';
	/** @var string ENUM_COLOR_GREEN */
	public const ENUM_COLOR_GREEN = 'green';
	/** @var string ENUM_COLOR_ORANGE */
	public const ENUM_COLOR_ORANGE = 'orange';
	/** @var string ENUM_COLOR_RED */
	public const ENUM_COLOR_RED = 'red';
	/** @var string ENUM_COLOR_PINK */
	public const ENUM_COLOR_PINK = 'pink';

	/** @var string ENUM_CONTENT_AREA_MAIN The main content area (panel body) */
	public const ENUM_CONTENT_AREA_MAIN = 'main';
	/** @var string ENUM_CONTENT_AREA_TOOLBAR The toolbar content area (for actions) */
	public const ENUM_CONTENT_AREA_TOOLBAR = 'toolbar';

	/** @var string Icon should be contained (boxed) in the medallion, best for icons with transparent background and some margin around */
	public const ENUM_ICON_COVER_METHOD_CONTAIN = 'contain';
	/** @var string Icon should be a litte zoomed out to cover almost all space, best for icons with transparent background and no margin around (eg. class icons) */
	public const ENUM_ICON_COVER_METHOD_ZOOMOUT = 'zoomout';
	/** @var string Icon should cover all the space, best for icons with filled background */
	public const ENUM_ICON_COVER_METHOD_COVER = 'cover';

	/** @var string DEFAULT_COLOR */
	public const DEFAULT_COLOR = self::ENUM_COLOR_NEUTRAL;
	/** @var string Default color for a panel displaying info about a datamodel class */
	public const DEFAULT_COLOR_FOR_CLASS = self::ENUM_COLOR_BLUE;
	/** @var null */
	public const DEFAULT_ICON_URL = null;
	/** @var string */
	public const DEFAULT_ICON_COVER_METHOD = self::ENUM_ICON_COVER_METHOD_CONTAIN;
	/** @var bool */
	public const DEFAULT_ICON_AS_MEDALLION = false;

	/** @var iUIContentBlock $oTitleBlock */
	protected $oTitleBlock;
	/** @var iUIContentBlock */
	protected $oSubTitleBlock;
	/** @var null|string $sIconUrl */
	protected $sIconUrl;
	/** @var string How the icon should cover its container, see static::ENUM_ICON_COVER_METHOD_XXX */
	protected $sIconCoverMethod;
	/** @var bool Whether the icon should be rendered as a medallion (rounded with border) or a standalone image */
	protected $bIconAsMedallion;
	/** @var string $sColor */
	protected $sColor;
	/** @var bool $bIsCollapsible */
	protected $bIsCollapsible;

	/**
	 * Panel constructor.
	 *
	 * @param string $sTitle
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock[] $aSubBlocks
	 * @param string $sColor
	 * @param string|null $sId
	 */
	public function __construct(string $sTitle = '', array $aSubBlocks = [], string $sColor = self::DEFAULT_COLOR, ?string $sId = null)
	{
		parent::__construct($sId);

		if (empty($sTitle)) {
			$this->oTitleBlock = new UIContentBlock();
		} else {
			$this->SetTitle($sTitle);
		}

		$this->oSubTitleBlock = new UIContentBlock();
		$this->aSubBlocks = $aSubBlocks;
		$this->sIconUrl = static::DEFAULT_ICON_URL;
		$this->sIconCoverMethod = static::DEFAULT_ICON_COVER_METHOD;
		$this->bIconAsMedallion = static::DEFAULT_ICON_AS_MEDALLION;
		$this->sColor = $sColor;
		$this->SetMainBlocks([]);
		$this->SetToolBlocks([]);
		$this->bIsCollapsible = false;
	}

	/**
	 * @see static::$oTitleBlock
	 * @return bool
	 */
	public function HasTitle(): bool
	{
		return $this->oTitleBlock->HasSubBlocks();
	}

	/**
	 * @see static::$oTitleBlock
	 * @return \Combodo\iTop\Application\UI\Base\Layout\iUIContentBlock
	 */
	public function GetTitleBlock()
	{
		return $this->oTitleBlock;
	}

	/**
	 * Set the title from the $oBlock, replacing any existing content
	 *
	 * @param \Combodo\iTop\Application\UI\Base\Layout\iUIContentBlock $oBlock
	 *
	 * @return $this
	 */
	public function SetTitleBlock(iUIContentBlock $oBlock)
	{
		$this->oSubTitleBlock = $oBlock;

		return $this;
	}

	/**
	 * Helper to set the title from a simple text ($sTitle), replacing any existnig block
	 *
	 * @see static::$oTitleBlock
	 *
	 * @param string $sTitle
	 *
	 * @return $this
	 */
	public function SetTitle(string $sTitle)
	{
		$this->oTitleBlock = new UIContentBlock();
		$this->oTitleBlock->AddHtml(utils::EscapeHtml($sTitle));

		return $this;
	}

	/**
	 * Add a UIBlock to the title
	 *
	 * @see static::$oTitleBlock
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock $oBlock
	 *
	 * @return $this
	 */
	public function AddTitleBlock(iUIBlock $oBlock)
	{
		$this->oTitleBlock->AddSubBlock($oBlock);

		return $this;
	}

	/**
	 * Add all $aBlocks to the title
	 *
	 * @see static::$oTitleBlock
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock[] $aBlocks
	 *
	 * @return $this
	 */
	public function AddTitleBlocks(array $aBlocks)
	{
		foreach ($aBlocks as $oBlock) {
			$this->AddTitleBlock($oBlock);
		}

		return $this;
	}

	/**
	 * @see static::$oSubTitleBlock
	 * @return bool
	 */
	public function HasSubTitle(): bool
	{
		return $this->oSubTitleBlock->HasSubBlocks();
	}

	/**
	 * @see static::$oSubTitleBlock
	 * @return \Combodo\iTop\Application\UI\Base\Layout\iUIContentBlock
	 */
	public function GetSubTitleBlock()
	{
		return $this->oSubTitleBlock;
	}

	/**
	 * Set the subtitle from the $oBlock, replacing any existing content
	 *
	 * @see static::$oSubTitleBlock
	 *
	 * @param \Combodo\iTop\Application\UI\Base\Layout\iUIContentBlock $oBlock
	 *
	 * @return $this
	 */
	public function SetSubTitleBlock(iUIContentBlock $oBlock)
	{
		$this->oSubTitleBlock = $oBlock;

		return $this;
	}

	/**
	 * Helper to set the subtitle from a simple text ($sSubTitle), replacing any existing block
	 *
	 * @see static::$oSubTitleBlock
	 *
	 * @param string $sSubTitle
	 *
	 * @return $this
	 */
	public function SetSubTitle(string $sSubTitle)
	{
		$this->oSubTitleBlock = new UIContentBlock();
		$this->oSubTitleBlock->AddHtml(utils::EscapeHtml($sSubTitle));

		return $this;
	}

	/**
	 * Add a UIBlock to the subtitle
	 *
	 * @see static::$oSubTitleBlock
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock $oBlock
	 *
	 * @return $this
	 */
	public function AddSubTitleBlock(iUIBlock $oBlock)
	{
		$this->oSubTitleBlock->AddSubBlock($oBlock);

		return $this;
	}

	/**
	 * Add all $aBlocks to the subtitle
	 *
	 * @see static::$oSubTitleBlock
	 *
	 * @param iUIBlock[] $aBlocks
	 *
	 * @return $this
	 */
	public function AddSubTitleBlocks(array $aBlocks)
	{
		foreach ($aBlocks as $oBlock) {
			$this->AddSubTitleBlock($oBlock);
		}

		return $this;
	}

	/**
	 * @see static::$sIconUrl
	 * @return bool
	 */
	public function HasIcon(): bool
	{
		return !empty($this->sIconUrl);
	}

	/**
	 * @see static::$sIconUrl
	 * @return null|string
	 */
	public function GetIconUrl(): ?string
	{
		return $this->sIconUrl;
	}

	/**
	 * @see static::$sIconCoverMethod
	 * @return string
	 */
	public function GetIconCoverMethod(): string
	{
		return $this->sIconCoverMethod;
	}

	/**
	 * @return bool True if the icon should be displayed as a medallion (round with a border) or as-is.
	 */
	public function IsIconAsMedallion(): bool
	{
		return $this->HasIcon() && $this->bIconAsMedallion;
	}

	/**
	 * @see static::$sIconUrl
	 * @see static::$sIconCoverMethod
	 * @see static::$bIconAsMedallion
	 *
	 * @param string $sIconUrl
	 * @param string $sIconCoverMethod
	 * @param bool $bIconAsMedallion
	 *
	 * @return $this
	 */
	public function SetIcon(string $sIconUrl, string $sIconCoverMethod = self::DEFAULT_ICON_COVER_METHOD, bool $bIconAsMedallion = self::DEFAULT_ICON_AS_MEDALLION)
	{
		$this->sIconUrl = $sIconUrl;
		$this->sIconCoverMethod = $sIconCoverMethod;
		$this->bIconAsMedallion = $bIconAsMedallion;

		return $this;
	}

	/**
	 * @return string
	 */
	public function GetColor()
	{
		return $this->sColor;
	}

	/**
	 * @param string $sColor
	 *
	 * @return $this
	 */
	public function SetColor(string $sColor)
	{
		$this->sColor = $sColor;

		return $this;
	}

	/**
	 * Set the panel's color from an ormStyle directly.
	 *
	 * Use cases:
	 * - Display information about a datamodel class
	 * - Display information about a particular enum value (linked objects)
	 *
	 * @param \ormStyle $oStyle
	 *
	 * @return $this
	 */
	public function SetColorFromOrmStyle(ormStyle $oStyle)
	{
		$sColor = empty($oStyle->GetMainColor()) ? static::DEFAULT_COLOR : $oStyle->GetMainColor();
		$this->SetColor($sColor);

		return $this;
	}

	/**
	 * Set the panel's color to the one corresponding to the $sClass datamodel class
	 *
	 * @param string $sClass
	 *
	 * @return $this
	 */
	public function SetColorFromClass(string $sClass)
	{
		$oStyle = MetaModel::GetClassStyle($sClass);
		if (empty($oStyle)) {
			$this->SetColor(static::DEFAULT_COLOR_FOR_CLASS);
		} else {
			$this->SetColorFromOrmStyle($oStyle);
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	public function IsCollapsible(): bool
	{
		return $this->bIsCollapsible;
	}

	/**
	 * @param bool $bIsCollapsible
	 *
	 * @return $this
	 */
	public function SetIsCollapsible(bool $bIsCollapsible)
	{
		$this->bIsCollapsible = $bIsCollapsible;
		return $this;
	}


	//----------------------
	// Specific content area
	//----------------------

	/**
	 * Set all main blocks at once.
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock[] $aBlocks
	 *
	 * @return $this
	 */
	public function SetMainBlocks(array $aBlocks) {
		$this->SetContentAreaBlocks(static::ENUM_CONTENT_AREA_MAIN, $aBlocks);

		return $this;
	}

	/**
	 * Return all the main blocks
	 *
	 * @return \Combodo\iTop\Application\UI\Base\iUIBlock[]
	 * @throws \Exception
	 */
	public function GetMainBlocks(): array {
		return $this->GetContentAreaBlocks(static::ENUM_CONTENT_AREA_MAIN);
	}

	/**
	 * Add the $oBlock to the main blocks.
	 * Note that if a block with the same ID already exists, it will be replaced.
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock $oBlock
	 *
	 * @return $this
	 */
	public function AddMainBlock(iUIBlock $oBlock)
	{
		$this->AddBlockToContentArea(static::ENUM_CONTENT_AREA_MAIN, $oBlock);

		return $this;
	}

	/**
	 * Add all $aBlocks to the main blocks
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock[] $aBlocks
	 *
	 * @return $this
	 * @uses static::AddBlocksToContentArea()
	 */
	public function AddMainBlocks(array $aBlocks)
	{
		$this->AddBlocksToContentArea(static::ENUM_CONTENT_AREA_MAIN, $aBlocks);

		return $this;
	}

	/**
	 * Remove the main block identified by $sBlockId.
	 * Note that if no block with that ID exists, it will proceed silently.
	 *
	 * @param string $sBlockId
	 *
	 * @return $this
	 */
	public function RemoveMainBlock(string $sBlockId)
	{
		$this->RemoveBlockFromContentArea(static::ENUM_CONTENT_AREA_MAIN, $sBlockId);

		return $this;
	}

	/**
	 * Set all toolbar blocks at once.
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock[] $aBlocks
	 *
	 * @return $this
	 */
	public function SetToolBlocks(array $aBlocks) {
		$this->SetContentAreaBlocks(static::ENUM_CONTENT_AREA_TOOLBAR, $aBlocks);

		return $this;
	}

	/**
	 * Return all the toolbar blocks
	 *
	 * @return \Combodo\iTop\Application\UI\Base\iUIBlock[]
	 * @throws \Exception
	 */
	public function GetToolbarBlocks(): array {
		return $this->GetContentAreaBlocks(static::ENUM_CONTENT_AREA_TOOLBAR);
	}

	/**
	 * Add the $oBlock to the toolbar blocks.
	 * Note that if a block with the same ID already exists, it will be replaced.
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock $oBlock
	 *
	 * @return $this
	 */
	public function AddToolbarBlock(iUIBlock $oBlock)
	{
		$this->AddBlockToContentArea(static::ENUM_CONTENT_AREA_TOOLBAR, $oBlock);

		return $this;
	}

	/**
	 * Add all $aBlocks to the toolbar blocks
	 *
	 * @param \Combodo\iTop\Application\UI\Base\iUIBlock[] $aBlocks
	 *
	 * @return $this
	 * @uses static::AddBlocksToContentArea()
	 */
	public function AddToolbarBlocks(array $aBlocks)
	{
		$this->AddBlocksToContentArea(static::ENUM_CONTENT_AREA_TOOLBAR, $aBlocks);

		return $this;
	}

	/**
	 * Remove the toolbar block identified by $sBlockId.
	 * Note that if no block with that ID exists, it will proceed silently.
	 *
	 * @param string $sBlockId
	 *
	 * @return $this
	 */
	public function RemoveToolbarBlock(string $sBlockId)
	{
		$this->RemoveBlockFromContentArea(static::ENUM_CONTENT_AREA_TOOLBAR, $sBlockId);

		return $this;
	}

	//-------------------------------
	// iUIContentBlock implementation
	//-------------------------------

	/**
	 * @inheritDoc
	 */
	public function AddHtml(string $sHtml) {
		$oBlock = new Html($sHtml);
		$this->AddMainBlock($oBlock);

		return $this;
	}

	/**
	 * Add the $oSubBlock directly in the main area
	 *
	 * @inheritDoc
	 */
	public function AddSubBlock(?iUIBlock $oSubBlock) {
		if ($oSubBlock) {
			$this->AddMainBlock($oSubBlock);
		}
		return $this;
	}

	/**
	 * Remove a specified subBlock from all the areas
	 *
	 * @param string $sId
	 *
	 * @return $this
	 */
	public function RemoveSubBlock(string $sId) {
		foreach ($this->GetContentAreas() as $oContentArea) {
			$oContentArea->RemoveSubBlock($sId);
		}

		return $this;
	}

	/**
	 * Check if the specified subBlock is within one of all the areas
	 *
	 * @param string $sId
	 *
	 * @return bool
	 */
	public function HasSubBlock(string $sId): bool {
		foreach ($this->GetContentAreas() as $oContentArea) {
			if ($oContentArea->HasSubBlock($sId)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a specific subBlock within all the areas
	 *
	 * @inheritDoc
	 */
	public function GetSubBlock(string $sId): ?iUIBlock {
		foreach ($this->GetContentAreas() as $oContentArea) {
			$oSubBlock = $oContentArea->GetSubBlock($sId);
			if (!is_null($oSubBlock)) {
				return $oSubBlock;
			}
		}

		return null;
	}

	/**
	 * Set the MAIN AREA subBlocks
	 *
	 * @inheritDoc
	 * @return $this|\Combodo\iTop\Application\UI\Base\Layout\iUIContentBlock
	 */
	public function SetSubBlocks(array $aSubBlocks): iUIContentBlock {
		$this->SetMainBlocks($aSubBlocks);

		return $this;
	}

	/**
	 * Get ALL the blocks in all the areas
	 *
	 * @inheritDoc
	 */
	public function GetSubBlocks(): array
	{
		$aSubBlocks = [];
		foreach ($this->GetContentAreas() as $oContentArea) {
			$aSubBlocks = array_merge($aSubBlocks, $oContentArea->GetSubBlocks());
		}

		return $aSubBlocks;
	}

}
