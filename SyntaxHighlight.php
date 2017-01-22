<?php

/*
 * Somewhat modified and extended PHP syntax highlighting class
 * with a little help from various answers on stackoverflow.com
 * by Stefan Liehmann <listef@listef.de>
 * Original:
 * Generic Syntax Highlighting with Regular Expressions
 * http://phoboslab.org/log/2007/08/generic-syntax-highlighting-with-regular-expressions
 * by Dominic Szablewski <mail@phoboslab.org>
 */
class SyntaxHighlight {

    public static function process($s, $lang = "") {
		switch ($lang) {
			case "CSS":
				return self::highlight_css($s);
				break;
			case "HTML":
				return self::highlight_html($s);
				break;
			case "PHP":
				// use preg_replace_callback in case of multiple PHP section inside a HTML code block
				$s = preg_replace_callback(
						"/((<\?php)|(&lt;\?php)|(&amp;&lt;\?php))(\s*.*?\s*)((\?>)|(\?&gt;)|(\?&amp;&gt;))/is",
						function ($match) { return self::highlight_generic(html_entity_decode($match[0])); },
						$s
					 );
				return $s;
				break;
			default:
				return self::highlight_generic(html_entity_decode($s));
				break;
		}
	}
	function highlight_html($s) {
		$out='';
		$html = html_entity_decode($s);
		preg_match_all(
			'/(?:(?:<(\/?)(\w+)'.
			'((?:\h+(?:\w+\h*=\h*)?".+?"|[^>]+)*|'.
			'\h+.+?)(\h*\/?)>)|(.+?))/s',
			$html,$matches,PREG_SET_ORDER
		);
		foreach ($matches as $match) {
			if ($match[2]) {
				$out.='<span class="html_tag">&lt;'.
					$match[1].$match[2].'</span>';
				if ($match[3]) {
					preg_match_all(
						'/(?:\h+(?:(?:(\w+)\h*=\h*)?'.
						'(".+?")|(.+)))/',
						$match[3],$parts,PREG_SET_ORDER
					);
					foreach ($parts as $part)
						$out.=' '.
							(empty($part[3])?
								((empty($part[1])?
									'':
									('<span class="html_attr">'.
										$part[1].'</span>=')).
								'<span class="html_data">'.
									$part[2].'</span>'):
								('<span class="html_tag">'.
									$part[3].'</span>'));
				}
				$out.='<span class="html_tag">'.
					$match[4].'&gt;</span>';
			}
			else
				$out.=htmlspecialchars($match[5]);
		}
		// handle doctype
		$out = preg_replace_callback("/(<!doctype.*?>)|(&lt;!doctype.*?&gt;)|(&amp;lt;!doctype.*?&amp;gt;)/i",
						function ($match) { return "<span class=\"html_tag\">$match[0]</span>"; },
                                                $out
                                         );

		return $out ? trim($out) : $s;

	}
	/*
	 * Trys to give CSS section names, keys, values and comments different HTML classes
	 * by Stefan Liehmann (listef@listef.de)
	 * idea from: http://stackoverflow.com/questions/3618381/parse-a-css-file-with-php
	 *
	 * Known issue:
	 * - there must be no colon (:) in a comment
	 */
	public static function highlight_css($s) {
		// helper function
		function _escapeComments($text) {
			$out = "";
			if ((strpos($text, "/*") !== FALSE) && (strpos($text, "*/") !== FALSE)) {
				$out .= str_replace(array("/*","*/"),array("<span class=\"C\">/*","*/</span>"), $text);
			} else {
				$out .= $text;
			}
			return $out;
		}

		$out = "";
		// matches on "anything (1), possible space, curly bracket, possible space, anything (2), close curly bracket"
		preg_match_all('/(.+?)\s?\{\s?(.+?)\s?\}/s', $s, $matches);

		foreach($matches[1] as $i=>$string) {
			// give section names and comments different class names
			$out .= "<span class=\"S\">" . _escapeComments($matches[1][$i]) . "</span> {";
			foreach(explode("\n", $matches[2][$i]) as $attr) {
				if ($attr != "") { // if there wasn't a "\n"
					// explode string $attr in name and value at :
					$key_val = explode(':', $attr);
					if (count($key_val)>1) { // there was a ":"
						// give section keys, values and inline comments different class names
						$out .= "    <span class=\"K\">" . trim($key_val[0]) . "</span>: <span class=\"D\">" . str_replace(";","",(trim(_escapeComments($key_val[1])))) . "</span>;";
					} else { // otherwise a comment is assumed
						$out .= "    <span class=\"C\">" . _escapeComments(trim($attr)) . "</span>";
					}
				} else { // there was a \n -> put it in again
					$out .= "\n";
				}
			}
			$out .= "}";
		}
		return ($out) ? trim($out) : $s;
	}
	/*
	 * - original function by Dominic Szablewski (mail@phoboslab.org)
	 * - changes by Stefan Liehmann (listef@listef.de):
	 * 		· use 'preg_replace_callback' for PHP7 compatibility
	 * 		· added some keywords
	 * 		· sorted keywords alphabetically
	 * 		· added rule for PHP start end end tags
	 */
    public static function highlight_generic($s) {
        $s = htmlspecialchars(trim($s));

        // Workaround for escaped backslashes
        $s = str_replace( '\\\\','\\\\<e>', $s );
        $tokens = array(); // This array will be filled from the regexp-callback

        $regexp = array(
            // Comments/Strings
            '/(
                \/\*.*?\*\/|
                \/\/.*?\n|
                \#.*?\n|
                (?<!\\\)&quot;.*?(?<!\\\)&quot;|
                (?<!\\\)\'(.*?)(?<!\\\)\'
            )/isx'
            => function($matches) use (&$tokens) {return self::replaceId($tokens,$matches[1]);},

            // Numbers (also look for Hex)
            '/(?<!\w)(
                0x[\da-f]+|
                \d+
            )(?!\w)/ix'
            => function($matches){return '<span class="N">'.$matches[1].'</span>';},

            // Make the bold assumption that an all uppercase word has a
            // special meaning
            '/(?<!\w|>)(
                [A-Z_0-9]{2,}
            )(?!\w)/x'
            => function($matches){return '<span class="D">'.$matches[1].'</span>';},

            // Keywords
            '/(?<!\w|\$|\%|\@|>)('
				// programming languages
				.'abstract|and|array|array|array_cast|array_splice|as|
				bool|boolean|break|
				case|catch|char|class|clone|close|const|continue|
				declare|default|define|delete|die|do|double|
				echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|
				endwhile|eval|exit|exit|explode|extends|eventsource|
				false|file|file_exists|final|finally|float|flush|for|foreach|function|
				global|goto|
				header|
				if|implements|implode|include|include_once|ini_set|instanceof|int|integer|interface|isset|
				json_encode|json_decode
				list|long|
				namespace|new|new|null|
				ob_flush|object|on|or|
				parse|print|private|protected|public|published|
				real|require|require_once|resource|return|
				self|short|signed|sleep|static|string|struct|switch|
				then|this|throw|true|try|
				unset|unsigned|use|usleep|
				var|var|void|
				while|
				xor|'
				// Apache config
				.'RewriteEngine|RewriteRule|ErrorDocument
            )(?!\w|=")/ix'
            => function($matches){return '<span class="K">'.$matches[1].'</span>';},

            // PHP/Perl-Style Vars: $var, %var, @var
            '/(?<!\w)(
                (\$|\%|\@)(\-&gt;|\w)+
            )(?!\w)/ix'
            => function($matches){return '<span class="V">'.$matches[1].'</span>';},

            // PHP start and end tags
            '/(<\?php)|(&lt;\?php)|(&amp;lt;\?php)|(\?>)|(\?&gt;)|(\?&amp;gt;)/ix'
            => function($matches){return '<span class="D">'.$matches[0].'</span>';}
        );

		foreach($regexp as $key=>$value) {
			$s = preg_replace_callback( $key,
										$value,
										$s );
		}

        // Paste the comments and strings back in again
        $s = str_replace( array_keys($tokens), array_values($tokens), $s );

        // Delete the "Escaped Backslash Workaround Token" (TM) and replace
        // tabs with four spaces.
        $s = str_replace( array( '<e>', "\t" ), array( '', '    ' ), $s );

        return $s;
    }

    // Regexp-Callback to replace every comment or string with a uniqid and save
    // the matched text in an array
    // This way, strings and comments will be stripped out and wont be processed
    // by the other expressions searching for keywords etc.
    private static function replaceId( &$a, $match ) {
        $id = "##r".uniqid()."##";

        // String or Comment?
        if( $match{0} == '/' || $match{0} == '#' ) {
			//error_log($match);
            $a[$id] = '<span class="C">'.str_replace("\n","",$match)."</span>\n";
        } else {
            $a[$id] = '<span class="S">'.$match.'</span>';
        }
        return $id;
    }
}
?>
