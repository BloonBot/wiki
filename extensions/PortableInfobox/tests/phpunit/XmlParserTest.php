<?php
/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\XmlParser
 */
class XmlParserTest extends MediaWikiTestCase {

	/** @dataProvider contentTagsDataProvider */
	public function testXHTMLParsing( $tag, $content ) {
		$markup = "<data source=\"asdfd\"><{$tag}>{$content}</{$tag}></data>";
		$result = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );

		$this->assertEquals( $content, (string)$result->{$tag} );
	}

	public function contentTagsDataProvider() {
		return [
			[ 'default', 'sadf <br> sakdjfl' ],
			[ 'format', '<>' ],
			[ 'label', '' ]
		];
	}

	/** @dataProvider errorHandlingDataProvider */
	public function testErrorHandling( $markup, $expectedErrors ) {
		$errors = [];
		try {
			$data = PortableInfobox\Parser\XmlParser::parseXmlString( $markup, $errors );
		} catch ( PortableInfobox\Parser\XmlMarkupParseErrorException $e ) {
			// parseXmlString should throw an exception, but we want to proceed in order to check errors
		}
		$this->assertEquals( $expectedErrors, array_map(
			function ( LibXMLError $error ) {
				return [
					'level' => $error->level,
					'code' => $error->code,
					'msg' => trim( $error->message )
				];
			},
			$errors
		) );
	}

	public function errorHandlingDataProvider() {
		/*
		 * Error codes are defined on official xml API documentation:
		 * http://www.xmlsoft.org/html/libxml-xmlerror.html
		 */
		return [
			[
				'<data>d</dat/a>',
				[
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 73,
						'msg' => "expected '>'"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 76,
						'msg' => "Opening and ending tag mismatch: data line 1 and dat"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 5,
						'msg' => "Extra content at the end of the document"
					]
				]
			],
			[
				'<data> x </data></data>',
				[
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 5,
						'msg' => "Extra content at the end of the document"
					]
				]
			],
			[
				'<data> > ddd < a ></data>',
				[
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 68,
						'msg' => "StartTag: invalid element name"
					]
				]
			],
			[
				'<data>',
				[
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 77,
						'msg' => "Premature end of data in tag data line 1"
					]
				]
			],
			[
				'<infobox><data source=caption></infobox>',
				[
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 39,
						'msg' => "AttValue: \" or ' expected"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 65,
						'msg' => "attributes construct error"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 73,
						'msg' => "Couldn't find end of Start Tag data line 1"
					]
				]
			],
			[
				'<infobox><data source="caption"></infobox>',
				[
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 76,
						'msg' => "Opening and ending tag mismatch: data line 1 and infobox"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 77,
						'msg' => "Premature end of data in tag infobox line 1"
					]
				]
			],
			[
				'<infobox><data source="caption></data></infobox>',
				[
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 38,
						'msg' => "Unescaped '<' not allowed in attributes values"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 65,
						'msg' => "attributes construct error"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 73,
						'msg' => "Couldn't find end of Start Tag data line 1"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 76,
						'msg' => "Opening and ending tag mismatch: infobox line 1 and data"
					],
					[
						'level' => LIBXML_ERR_FATAL,
						'code' => 5,
						'msg' => "Extra content at the end of the document"
					]
				]
			]
		];
	}

	/**
	 * @dataProvider entitiesTestDataProvider
	 */
	public function testHTMLEntities( $markup, $expectedResult ) {
		$result = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );
		$this->assertEquals( $expectedResult, $result[0] );
	}

	public function entitiesTestDataProvider() {
		return [
			[ '<data></data>', '' ],
			[ '<data>&aksjdf;</data>', '&aksjdf;' ],
			[ '<data>&amp;</data>', '&' ],
			[ '<data>&middot;</data>', '??' ],
			[ '<data>&Uuml;</data>', '??' ],
			[ '<data>&Delta;</data>', '??' ],
			[ '<data>&amp;amp;</data>', '&amp;' ],
			[ '<data>&amp</data>', '&amp' ]
		];
	}
}
