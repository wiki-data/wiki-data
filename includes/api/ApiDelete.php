<?php
/**
 *
 *
 * Created on Jun 30, 2007
 *
 * Copyright © 2007 Roan Kattouw <Firstname>.<Lastname>@gmail.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * API module that facilitates deleting pages. The API equivalent of action=delete.
 * Requires API write mode to be enabled.
 *
 * @ingroup API
 */
class ApiDelete extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Extracts the title, token, and reason from the request parameters and invokes
	 * the local delete() function with these as arguments. It does not make use of
	 * the delete function specified by Article.php. If the deletion succeeds, the
	 * details of the article deleted and the reason for deletion are added to the
	 * result object.
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$this->requireOnlyOneParameter( $params, 'title', 'pageid' );

		if ( isset( $params['title'] ) ) {
			$titleObj = Title::newFromText( $params['title'] );
			if ( !$titleObj ) {
				$this->dieUsageMsg( array( 'invalidtitle', $params['title'] ) );
			}
		} elseif ( isset( $params['pageid'] ) ) {
			$titleObj = Title::newFromID( $params['pageid'] );
			if ( !$titleObj ) {
				$this->dieUsageMsg( array( 'nosuchpageid', $params['pageid'] ) );
			}
		}
		if ( !$titleObj->exists() ) {
			$this->dieUsageMsg( 'notanarticle' );
		}

		$reason = ( isset( $params['reason'] ) ? $params['reason'] : null );
		$pageObj = WikiPage::factory( $titleObj );
		$user = $this->getUser();

		if ( $titleObj->getNamespace() == NS_FILE ) {
			$retval = self::deleteFile( $pageObj, $user, $params['token'], $params['oldimage'], $reason, false );
		} else {
			$retval = self::delete( $pageObj, $user, $params['token'], $reason );
		}

		if ( count( $retval ) ) {
			$this->dieUsageMsg( reset( $retval ) ); // We don't care about multiple errors, just report one of them
		}

		// Deprecated parameters
		if ( $params['watch'] ) {
			$watch = 'watch';
		} elseif ( $params['unwatch'] ) {
			$watch = 'unwatch';
		} else {
			$watch = $params['watchlist'];
		}
		$this->setWatch( $watch, $titleObj, 'watchdeletion' );

		$r = array( 'title' => $titleObj->getPrefixedText(), 'reason' => $reason );
		$this->getResult()->addValue( null, $this->getModuleName(), $r );
	}

	/**
	 * @param $title Title
	 * @param $user User doing the action
	 * @param $token String
	 * @return array
	 */
	private static function getPermissionsError( $title, $user, $token ) {
		// Check permissions
		return $title->getUserPermissionsErrors( 'delete', $user );
	}

	/**
	 * We have our own delete() function, since Article.php's implementation is split in two phases
	 *
	 * @param $page WikiPage object to work on
	 * @param $user User doing the action
	 * @param $token String: delete token (same as edit token)
	 * @param $reason String: reason for the deletion. Autogenerated if NULL
	 * @return Title::getUserPermissionsErrors()-like array
	 */
	public static function delete( Page $page, User $user, $token, &$reason = null ) {
		if ( $page->isBigDeletion() && !$user->isAllowed( 'bigdelete' ) ) {
			global $wgDeleteRevisionsLimit;
			return array( array( 'delete-toobig', $wgDeleteRevisionsLimit ) );
		}

		$title = $page->getTitle();
		$errors = self::getPermissionsError( $title, $user, $token );
		if ( count( $errors ) ) {
			return $errors;
		}

		// Auto-generate a summary, if necessary
		if ( is_null( $reason ) ) {
			// Need to pass a throwaway variable because generateReason expects
			// a reference
			$hasHistory = false;
			$reason = $page->getAutoDeleteReason( $hasHistory );
			if ( $reason === false ) {
				return array( array( 'cannotdelete', $title->getPrefixedText() ) );
			}
		}

		$error = '';
		// Luckily, Article.php provides a reusable delete function that does the hard work for us
		if ( $page->doDeleteArticle( $reason, false, 0, true, $error ) ) {
			return array();
		} else {
			return array( array( 'cannotdelete', $title->getPrefixedText() ) );
		}
	}

	/**
	 * @param $page WikiPage object to work on
	 * @param $user User doing the action
	 * @param $token
	 * @param $oldimage
	 * @param $reason
	 * @param $suppress bool
	 * @return \type|array|Title
	 */
	public static function deleteFile( Page $page, User $user, $token, $oldimage, &$reason = null, $suppress = false ) {
		$title = $page->getTitle();
		$errors = self::getPermissionsError( $title, $user, $token );
		if ( count( $errors ) ) {
			return $errors;
		}

		$file = $page->getFile();
		if ( !$file->exists() || !$file->isLocal() || $file->getRedirected() ) {
			return self::delete( $page, $user, $token, $reason );
		}

		if ( $oldimage ) {
			if ( !FileDeleteForm::isValidOldSpec( $oldimage ) ) {
				return array( array( 'invalidoldimage' ) );
			}
			$oldfile = RepoGroup::singleton()->getLocalRepo()->newFromArchiveName( $title, $oldimage );
			if ( !$oldfile->exists() || !$oldfile->isLocal() || $oldfile->getRedirected() ) {
				return array( array( 'nodeleteablefile' ) );
			}
		} else {
			$oldfile = false;
		}

		if ( is_null( $reason ) ) { // Log and RC don't like null reasons
			$reason = '';
		}
		$status = FileDeleteForm::doDelete( $title, $file, $oldimage, $reason, $suppress );
		if ( !$status->isGood() ) {
			return array( array( 'cannotdelete', $title->getPrefixedText() ) );
		}

		return array();
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'title' => null,
			'pageid' => array(
				ApiBase::PARAM_TYPE => 'integer'
			),
			'token' => null,
			'reason' => null,
			'watch' => array(
				ApiBase::PARAM_DFLT => false,
				ApiBase::PARAM_DEPRECATED => true,
			),
			'watchlist' => array(
				ApiBase::PARAM_DFLT => 'preferences',
				ApiBase::PARAM_TYPE => array(
					'watch',
					'unwatch',
					'preferences',
					'nochange'
				),
			),
			'unwatch' => array(
				ApiBase::PARAM_DFLT => false,
				ApiBase::PARAM_DEPRECATED => true,
			),
			'oldimage' => null,
		);
	}

	public function getParamDescription() {
		$p = $this->getModulePrefix();
		return array(
			'title' => "Title of the page you want to delete. Cannot be used together with {$p}pageid",
			'pageid' => "Page ID of the page you want to delete. Cannot be used together with {$p}title",
			'token' => 'A delete token previously retrieved through prop=info',
			'reason' => 'Reason for the deletion. If not set, an automatically generated reason will be used',
			'watch' => 'Add the page to your watchlist',
			'watchlist' => 'Unconditionally add or remove the page from your watchlist, use preferences or do not change watch',
			'unwatch' => 'Remove the page from your watchlist',
			'oldimage' => 'The name of the old image to delete as provided by iiprop=archivename'
		);
	}

	public function getDescription() {
		return 'Delete a page';
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(),
			$this->getRequireOnlyOneParameterErrorMessages( array( 'title', 'pageid' ) ),
			array(
				array( 'invalidtitle', 'title' ),
				array( 'nosuchpageid', 'pageid' ),
				array( 'notanarticle' ),
				array( 'hookaborted', 'error' ),
				array( 'delete-toobig', 'limit' ),
				array( 'cannotdelete', 'title' ),
				array( 'invalidoldimage' ),
				array( 'nodeleteablefile' ),
			)
		);
	}

	public function needsToken() {
		return true;
	}

	public function getTokenSalt() {
		return '';
	}

	public function getExamples() {
		return array(
			'api.php?action=delete&title=Main%20Page&token=123ABC',
			'api.php?action=delete&title=Main%20Page&token=123ABC&reason=Preparing%20for%20move'
		);
	}

	public function getHelpUrls() {
		return 'http://www.mediawiki.org/wiki/API:Delete';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id: ApiDelete.php 103332 2011-11-16 15:57:56Z ialex $';
	}
}
