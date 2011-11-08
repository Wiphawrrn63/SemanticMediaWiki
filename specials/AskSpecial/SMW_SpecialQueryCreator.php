<?php

/**
 * This special page for Semantic MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @file SMW_SpecialQueryCreator.php
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Sergey Chernyshev
 * @author Devayon Das
 */
class SMWQueryCreatorPage extends SMWQueryUI {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'QueryCreator' );
	}

	/**
	 * The main method for creating the output page.
	 * Calls the various methods of SMWQueryUI and SMWQueryUIHelper to build
	 * UI elements and to process them.
	 *
	 * @global OutputPage $wgOut
	 * @param string $p
	 */
	protected function makePage( $p ) {
		global $wgOut;

		$htmlOutput = $this->makeForm( $p );

		if ( $this->uiCore->getQueryString() != "" ) {
			if ( $this->usesNavigationBar() ) {
				$navigationBar = $this->getNavigationBar (
					$this->uiCore->getLimit(),
					$this->uiCore->getOffset(),
					$this->uiCore->hasFurtherResults() );
				$navigation = Html::rawElement( 'div',
					array( 'class' => 'smwqcnavbar' ),
					$navigationBar );
			} else {
				$navigation = '';
			}

			$htmlOutput .= $navigation .
				Html::rawElement( 'div', array( 'class' => 'smwqcresult' ),
					$this->uiCore->getHTMLResult() ) .
				$navigation;

		}

		return $htmlOutput;
	}

	/**
	 * This method calls the various processXXXBox() methods for each
	 * of the corresponding getXXXBox() methods which the UI uses. Then it
	 * merges the results of these methods and return them.
	 *
	 * @global WebRequest $wgRequest
	 * @return array
	 */
	protected function processParams() {
		global $wgRequest;

		$params = array_merge(
			array(
				'format'  =>  $wgRequest->getVal( 'format' ),
				'offset'  =>  $wgRequest->getVal( 'offset',  '0'  ),
				'limit'   =>  $wgRequest->getVal( 'limit',   '20' ) ),
			$this->processPoSortFormBox( $wgRequest ),
			$this->processFormatSelectBox( $wgRequest )
		);
		return $params;
	}

	/**
	 * Displays a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @param string $format
	 * @param array $paramValues The current values for the parameters (name => value)
	 * @param array $ignoredAttribs Attributes which should not be generated by this method.
	 *
	 * @return string
	 *
	 * Overridden from parent to ignore some parameters.
	 */
	protected function showFormatOptions( $format, array $paramValues, array $ignoredAttribs = array() ) {
		return parent::showFormatOptions( $format, $paramValues, array(
			'format', 'limit', 'offset', 'mainlabel', 'intro', 'outro', 'default'
		) );
	}

	/**
	 * Create the search form.
	 *
	 * @return string HTML code for search form
	 */
	protected function makeForm() {
		SMWOutputs::requireResource( 'jquery' );

		$specTitle = $this->getTitle();
		$formatBox = $this->getFormatSelectBoxSep( 'broadtable' );

		$result = '<div class="smwqcerrors">' . $this->getErrorsHtml() . '</div>';

		$formParameters = array( 'name' => 'qc', 'id'=>'smwqcform',
			'action' => $specTitle->escapeLocalURL(), 'method' => 'get' );
		$result .= Html::openElement( 'form', $formParameters ) . "\n" .
			Html::hidden( 'title', $specTitle->getPrefixedText() ) .
			// Header:
			wfMsg( 'smw_qc_query_help' ) .
			 // Main query and format options:
			$this->getQueryFormBox() .
			// Sorting and prinouts:
			'<div class="smwqcsortbox">' . $this->getPoSortFormBox() . '</div>';

		// Control to show/hide additional options:
		$result .= '<div class="smwqcformatas">' .
			Html::element( 'strong', array(), wfMsg( 'smw_ask_format_as' ) ) .
			$formatBox[0] .
			'<span id="show_additional_options" style="display:inline;">' .
			'<a href="#addtional" rel="nofollow" onclick="' .
			 "jQuery('#additional_options').show('blind');" .
			 "document.getElementById('show_additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_show_addnal_opts' ) . '</a></span>' .
			'<span id="hide_additional_options" style="display:none"><a href="#" rel="nofollow" onclick="' .
			 "jQuery('#additional_options').hide('blind');;" .
			 "document.getElementById('hide_additional_options').style.display='none';" .
			 "document.getElementById('show_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_hide_addnal_opts' ) . '</a></span>' .
			'</div>';

		// Controls for additional options:
		$result .= '<div id="additional_options" style="display:none">' .
			$this->getOtherParametersBox() .
			'<fieldset><legend>' . wfMsg( 'smw_qc_formatopt' ) . "</legend>\n" .
			$formatBox[1] . // display the format options
			"</fieldset>\n" .
			'</div>'; // end of hidden additional options

		// Submit button and documentation link:
		$result .= '<br/><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/><br/>' .
			'<a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' .
			wfMsg( 'smw_ask_help' ) . '</a>';

		// Control for showing #ask syntax of query:
		if ( $this->uiCore->getQueryString() !== '' ) { // only show if query given
			$result .= ' | <a name="show-embed-code" id="show-embed-code" href="##" rel="nofollow">' .
				wfMsg( 'smw_ask_show_embed' ) . '</a>' .
				'<div id="embed-code-dialog">' . $this->getAskEmbedBox() . '</div>';

			SMWOutputs::requireResource( 'jquery.ui.autocomplete' );
			SMWOutputs::requireResource( 'jquery.ui.dialog' );
			SMWOutputs::requireResource( 'ext.smw.style' );

			$javascriptText = <<<EOT
<script type="text/javascript">
	jQuery( document ).ready( function(){
		jQuery( '#embed-code-dialog' ).dialog( {
			autoOpen:false,
			modal: true,
			buttons: {
				Ok: function(){
					jQuery( this ).dialog( "close" );
				}
			}
		} );
		jQuery( '#show-embed-code' ).bind( 'click', function(){
			jQuery( '#embed-code-dialog' ).dialog( "open" );
		} );
	} );
</script>
EOT;
			SMWOutputs::requireScript( 'smwToggleAskSyntaxQC', $javascriptText );
		}

		$result .= '<input type="hidden" name="eq" value="no"/>' .
			"\n</form><br/>";

		return $result;
	}

	/**
	 * Overridden to include form parameters.
	 *
	 * @return array of strings in the urlparamater=>value format
	 */
	protected function getUrlArgs() {
		$tmpArray = array();
		$params = $this->uiCore->getParameters();
		foreach ( $params as $key => $value ) {
			if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
				$tmpArray[$key] = $value;
			}
		}
		$this->setUrlArgs( $tmpArray );
		return $this->urlArgs;
	}

	/**
	 * Creates controls for limit, intro, outro, default and offset
	 *
	 * @return string
	 */
	protected function getOtherParametersBox() {
		$params = $this->uiCore->getParameters();
		if ( array_key_exists( 'limit', $params ) ) {
			$limit = $params['limit'];
		} else {
			$limit = '';
		}
		if ( array_key_exists( 'offset', $params ) ) {
			$offset = $params['offset'];
		} else {
			$offset = '';
		}
		if ( array_key_exists( 'intro', $params ) ) {
			$intro = $params['intro'];
		} else {
			$intro = '';
		}
		if ( array_key_exists( 'outro', $params ) ) {
			$outro = $params['outro'];
		} else {
			$outro = '';
		}
		if ( array_key_exists( 'default', $params ) ) {
			$default = $params['default'];
		} else {
			$default = '';
		}
		$result = '<fieldset><legend>' . wfMsg( 'smw_ask_otheroptions' ) . "</legend>\n" .
			Html::rawElement( 'div',
				array( 'style' => 'width: 30%; min-width:220px; margin:5px; padding: 1px; float: left;' ),
				wfMsg( 'smw_qc_intro' ) .
					'<input name="p[intro]" value="' . $intro . '" style="width:220px;"/> <br/>' .
					wfMsg( 'smw_paramdesc_intro' )
			) .
			Html::rawElement( 'div',
				array( 'style' => 'width: 30%; min-width:220px; margin:5px; padding: 1px; float: left;' ),
				wfMsg( 'smw_qc_outro' ) .
					'<input name="p[outro]" value="' . $outro . '" style="width:220px;"/> <br/>' .
					wfMsg( 'smw_paramdesc_outro' )
			) .
			Html::rawElement( 'div',
				array( 'style' => 'width: 30%; min-width:220px; margin:5px; padding: 1px; float: left;' ),
				wfMsg( 'smw_qc_default' ) .
					'<input name="p[default]" value="' . $default . '" style="width:220px;" /> <br/>' .
					wfMsg( 'smw_paramdesc_default' )
			) .
			Html::hidden( 'p[limit]', $limit ) .
			Html::hidden( 'p[offset]', $offset ) .
			'</fieldset>';

		return $result;
	}
}

