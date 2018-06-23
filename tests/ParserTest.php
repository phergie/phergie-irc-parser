<?php
/**
 * Phergie (http://phergie.org)
 *
 * @link http://github.com/phergie/phergie-irc-parser for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc
 */

namespace Phergie\Irc\Tests;

use Phergie\Irc\Parser;

/**
 * Tests for \Phergie\Irc\Parser.
 *
 * @category Phergie
 * @package Phergie\Irc
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests parse().
     *
     * @param string $message Parameter for parse() call
     * @param array|null $result Expected return value of parse()
     * @dataProvider dataProviderTestParse
     * @see \Phergie\Irc\Parser::parse()
     */
    public function testParse($message, $result)
    {
        $parser = new Parser;
        $this->assertEquals($result, $parser->parse($message));
    }

    /**
     * Tests parseAll().
     *
     * @param string $message Parameter for parseAll() call
     * @param array|null $result Expected return value of parseAll()
     * @dataProvider dataProviderTestParseAll
     * @see \Phergie\Irc\Parser::parseAll()
     */
    public function testParseAll($message, $result)
    {
        $parser = new Parser;
        $this->assertEquals($result, $parser->parseAll($message));
    }

    /**
     * Tests consume().
     *
     * @param string $message Parameter for consume() call
     * @param array|null $result Expected return value of consume()
     * @dataProvider dataProviderTestParse
     * @see \Phergie\Irc\Parser::consume()
     */
    public function testConsume($message, $result)
    {
        $parser = new Parser;
        $temp = $message;
        $this->assertEquals($result, $parser->consume($message));
        if ($result === null) {
            $this->assertEquals($message, $temp);
        } elseif (isset($result['tail'])) {
            $this->assertEquals($message, $result['tail']);
        }
    }

    /**
     * Tests consumeAll().
     *
     * @param string $message Parameter for consumeAll() call
     * @param array|null $result Expected return value of consumeAll()
     * @dataProvider dataProviderTestParseAll
     * @see \Phergie\Irc\Parser::consumeAll()
     */
    public function testConsumeAll($message, $result)
    {
        $parser = new Parser;
        $this->assertEquals($result, $parser->consumeAll($message));
        if ($result) {
            $last = $result;
            if (isset($result['tail'])) {
                $this->assertEquals($message, $result['tail']);
            }
        }
    }

    /**
     * Data provider for testParse() and testConsume().
     *
     * @return array
     */
    public function dataProviderTestParse()
    {
        $data = [

            // Empty message
            [
                '',
                null
            ],

            // No CRLF
            [
                'REHASH',
                null
            ],
            [
                'NICK :Elazar',
                null,
            ],

            // Data past the first message should be stored as 'tail'
            [
                "USER guest tolmoon tolsun :Ronnie Regan\r\nNICK :Wiz",
                [
                    'command' => 'USER',
                    'params' => [
                        'username' => 'guest',
                        'hostname' => 'tolmoon',
                        'servername' => 'tolsun',
                        'realname' => 'Ronnie Regan',
                        'all' => 'guest tolmoon tolsun :Ronnie Regan',
                    ],
                    'targets' => ['guest'],
                    'message' => "USER guest tolmoon tolsun :Ronnie Regan\r\n",
                    'tail' => 'NICK :Wiz',
                ],
            ],

            [
                ":this:message:is:invalid\r\nNICK :Wiz",
                [
                    'invalid' => ":this:message:is:invalid\r\n",
                    'tail' => 'NICK :Wiz',
                ],
            ],

            // PASS (RFC 1459 Section 4.1.1)
            [
                "PASS :secretpasswordhere\r\n",
                [
                    'command' => 'PASS',
                    'params' => [
                        'password' => 'secretpasswordhere',
                        'all' => ':secretpasswordhere',
                    ],
                    'targets' => ['secretpasswordhere'],
                ],
            ],

            // NICK (RFC 1459 Section 4.1.2)
            [
                "NICK :Wiz\r\n",
                [
                    'command' => 'NICK',
                    'params' => [
                        'nickname' => 'Wiz',
                        'all' => ':Wiz',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],
            
            [
                "NICK :Wiz_\r\n",
                [
                    'command' => 'NICK',
                    'params' => [
                        'nickname' => 'Wiz_',
                        'all' => ':Wiz_',
                    ],
                    'targets' => ['Wiz_'],
                ],
            ],
            
            // Nick with ~ character allowed
            [
                "NICK :Incredible~\r\n",
                [
                    'command' => 'NICK',
                    'params' => [
                        'nickname' => 'Incredible~',
                        'all' => ':Incredible~',
                    ],
                    'targets' => ['Incredible~'],
                ],
            ],

            [
                "NICK Wiz :1\r\n",
                [
                    'command' => 'NICK',
                    'params' => [
                        'nickname' => 'Wiz',
                        'hopcount' => '1',
                        'all' => 'Wiz :1',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            [
                ":WiZ NICK :Kilroy\r\n",
                [
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'NICK',
                    'params' => [
                        'nickname' => 'Kilroy',
                        'all' => ':Kilroy',
                    ],
                    'targets' => ['Kilroy'],
                ],
            ],

            // USER (RFC 1459 Section 4.1.3)
            [
                "USER guest tolmoon tolsun :Ronnie Regan\r\n",
                [
                    'command' => 'USER',
                    'params' => [
                        'username' => 'guest',
                        'hostname' => 'tolmoon',
                        'servername' => 'tolsun',
                        'realname' => 'Ronnie Regan',
                        'all' => 'guest tolmoon tolsun :Ronnie Regan',
                    ],
                    'targets' => ['guest'],
                ],
            ],

            [
                ":testnick USER guest tolmoon tolsun :Ronnie Regan\r\n",
                [
                    'prefix' => ':testnick',
                    'nick' => 'testnick',
                    'command' => 'USER',
                    'params' => [
                        'hostname' => 'tolmoon',
                        'realname' => 'Ronnie Regan',
                        'servername' => 'tolsun',
                        'username' => 'guest',
                        'all' => 'guest tolmoon tolsun :Ronnie Regan',
                    ],
                    'targets' => ['guest'],
                ],
            ],

            // SERVER (RFC 1459 Section 4.1.4)
            [
                "SERVER test.oulu.fi 1 :[tolsun.oulu.fi] Experimental server\r\n",
                [
                    'command' => 'SERVER',
                    'params' => [
                        'servername' => 'test.oulu.fi',
                        'hopcount' => '1',
                        'info' => '[tolsun.oulu.fi] Experimental server',
                        'all' => 'test.oulu.fi 1 :[tolsun.oulu.fi] Experimental server',
                    ],
                ],
            ],

            [
                ":tolsun.oulu.fi SERVER csd.bu.edu 5 :BU Central Server\r\n",
                [
                    'prefix' => ':tolsun.oulu.fi',
                    'servername' => 'tolsun.oulu.fi',
                    'command' => 'SERVER',
                    'params' => [
                        'servername' => 'csd.bu.edu',
                        'hopcount' => '5',
                        'info' => 'BU Central Server',
                        'all' => 'csd.bu.edu 5 :BU Central Server',
                    ],
                ],
            ],

            // OPER (RFC 1459 Section 4.1.5)
            [
                "OPER foo :bar\r\n",
                [
                    'command' => 'OPER',
                    'params' => [
                        'user' => 'foo',
                        'password' => 'bar',
                        'all' => 'foo :bar',
                    ],
                    'targets' => ['foo'],
                ],
            ],

            // QUIT (RFC 1459 Section 4.1.6)
            [
                "QUIT\r\n",
                [
                    'command' => 'QUIT',
                ],
            ],

            [
                "QUIT :Gone to have lunch\r\n",
                [
                    'command' => 'QUIT',
                    'params' => [
                        'message' => 'Gone to have lunch',
                        'all' => ':Gone to have lunch',
                    ],
                ],
            ],

            // SQUIT (RFC 1459 Section 4.1.7)
            [
                "SQUIT tolsun.oulu.fi :Bad Link ?\r\n",
                [
                    'command' => 'SQUIT',
                    'params' => [
                        'server' => 'tolsun.oulu.fi',
                        'comment' => 'Bad Link ?',
                        'all' => 'tolsun.oulu.fi :Bad Link ?',
                    ],
                ],
            ],

            [
                ":Trillian SQUIT cm22.eng.umd.edu :Server out of control\r\n",
                [
                    'prefix' => ':Trillian',
                    'nick' => 'Trillian',
                    'command' => 'SQUIT',
                    'params' => [
                        'server' => 'cm22.eng.umd.edu',
                        'comment' => 'Server out of control',
                        'all' => 'cm22.eng.umd.edu :Server out of control',
                    ],
                ],
            ],

            // JOIN (RFC 1459 Section 4.2.1)
            [
                "JOIN :#foobar\r\n",
                [
                    'command' => 'JOIN',
                    'params' => [
                        'channels' => '#foobar',
                        'all' => ':#foobar',
                    ],
                    'targets' => ['#foobar'],
                ],
            ],

            [
                "JOIN &foo :fubar\r\n",
                [
                    'command' => 'JOIN',
                    'params' => [
                        'channels' => '&foo',
                        'keys' => 'fubar',
                        'all' => '&foo :fubar',
                    ],
                    'targets' => ['&foo'],
                ],
            ],

            [
                "JOIN #foo,&bar :fubar\r\n",
                [
                    'command' => 'JOIN',
                    'params' => [
                        'channels' => '#foo,&bar',
                        'keys' => 'fubar',
                        'all' => '#foo,&bar :fubar',
                    ],
                    'targets' => ['#foo', '&bar'],
                ],
            ],

            [
                "JOIN #foo,#bar :fubar,foobar\r\n",
                [
                    'command' => 'JOIN',
                    'params' => [
                        'channels' => '#foo,#bar',
                        'keys' => 'fubar,foobar',
                        'all' => '#foo,#bar :fubar,foobar',
                    ],
                    'targets' => ['#foo', '#bar'],
                ],
            ],

            [
                "JOIN :#foo,#bar\r\n",
                [
                    'command' => 'JOIN',
                    'params' => [
                        'channels' => '#foo,#bar',
                        'all' => ':#foo,#bar',
                    ],
                    'targets' => ['#foo', '#bar'],
                ],
            ],

            [
                ":WiZ JOIN :#Twilight_zone\r\n",
                [
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'JOIN',
                    'params' => [
                        'channels' => '#Twilight_zone',
                        'all' => ':#Twilight_zone',
                    ],
                    'targets' => ['#Twilight_zone'],
                ],
            ],

            // PART (RFC 2182 Section 3.2.2)
            [
                "PART :#twilight_zone\r\n",
                [
                    'command' => 'PART',
                    'params' => [
                        'channels' => '#twilight_zone',
                        'all' => ':#twilight_zone',
                    ],
                    'targets' => ['#twilight_zone'],
                ],
            ],

            [
                "PART :#oz-ops,&group5\r\n",
                [
                    'command' => 'PART',
                    'params' => [
                        'channels' => '#oz-ops,&group5',
                        'all' => ':#oz-ops,&group5',
                    ],
                    'targets' => ['#oz-ops', '&group5'],
                ],
            ],

            [
                ":WiZ!jto@tolsun.oulu.fi PART #playzone :I lost\r\n",
                [
                    'prefix' => ':WiZ!jto@tolsun.oulu.fi',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'tolsun.oulu.fi',
                    'command' => 'PART',
                    'params' => [
                        'channels' => '#playzone',
                        'message' => 'I lost',
                        'all' => '#playzone :I lost',
                    ],
                    'targets' => ['#playzone'],
                ],
            ],

            // Some servers use weird cloaked hostnames, although it's not RFC conform
            [
                ":WiZ!jto@DCE7E23D:1D6D03E4:2248D1C4:IP PART #playzone :I lost\r\n",
                [
                    'prefix' => ':WiZ!jto@DCE7E23D:1D6D03E4:2248D1C4:IP',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'DCE7E23D:1D6D03E4:2248D1C4:IP',
                    'command' => 'PART',
                    'params' => [
                            'channels' => '#playzone',
                            'message' => 'I lost',
                            'all' => '#playzone :I lost',
                    ],
                    'targets' => ['#playzone'],
                ],
            ],
            [
                ":WiZ!jto@facebook/hhvm/sgolemon PART #playzone :I lost\r\n",
                [
                    'prefix' => ':WiZ!jto@facebook/hhvm/sgolemon',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'facebook/hhvm/sgolemon',
                    'command' => 'PART',
                    'params' => [
                            'channels' => '#playzone',
                            'message' => 'I lost',
                            'all' => '#playzone :I lost',
                    ],
                    'targets' => ['#playzone'],
                ],
            ],
            [
                ":WiZ!jto@gateway/web/irccloud.com/x-yjyvvvvrtuiwaqco PART #playzone :I lost\r\n",
                [
                    'prefix' => ':WiZ!jto@gateway/web/irccloud.com/x-yjyvvvvrtuiwaqco',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'gateway/web/irccloud.com/x-yjyvvvvrtuiwaqco',
                    'command' => 'PART',
                    'params' => [
                            'channels' => '#playzone',
                            'message' => 'I lost',
                            'all' => '#playzone :I lost',
                    ],
                    'targets' => ['#playzone'],
                ],
            ],

            // MODE (RFC 1459 Section 4.2.3)
            [
                "MODE #Finnish :+im\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#Finnish',
                        'mode' => '+im',
                        'all' => '#Finnish :+im',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            [
                "MODE #Finnish +o :Kilroy\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#Finnish',
                        'mode' => '+o',
                        'params' => 'Kilroy',
                        'user' => 'Kilroy',
                        'all' => '#Finnish +o :Kilroy',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            // Testing nicks with ~
            [
                "MODE #Finnish +o :Kilroy~\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#Finnish',
                        'mode' => '+o',
                        'params' => 'Kilroy~',
                        'user' => 'Kilroy~',
                        'all' => '#Finnish +o :Kilroy~',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            [
                "MODE #Finnish +v :Wiz\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#Finnish',
                        'mode' => '+v',
                        'params' => 'Wiz',
                        'user' => 'Wiz',
                        'all' => '#Finnish +v :Wiz',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            [
                "MODE #Finnish +ov :Kilroy Wiz\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#Finnish',
                        'mode' => '+ov',
                        'params' => 'Kilroy Wiz',
                        'all' => '#Finnish +ov :Kilroy Wiz',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            [
                "MODE #Finnish +mvv-v :Kilroy Wiz Angel\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#Finnish',
                        'mode' => '+mvv-v',
                        'params' => 'Kilroy Wiz Angel',
                        'all' => '#Finnish +mvv-v :Kilroy Wiz Angel',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            [
                "MODE #Fins :-s\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#Fins',
                        'mode' => '-s',
                        'all' => '#Fins :-s',
                    ],
                    'targets' => ['#Fins'],
                ],
            ],

            [
                "MODE #42 +k :oulu\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#42',
                        'mode' => '+k',
                        'params' => 'oulu',
                        'key' => 'oulu',
                        'all' => '#42 +k :oulu',
                    ],
                    'targets' => ['#42'],
                ],
            ],

            [
                "MODE #42 +ks :oulu\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#42',
                        'mode' => '+ks',
                        'params' => 'oulu',
                        'all' => '#42 +ks :oulu',
                    ],
                    'targets' => ['#42'],
                ],
            ],

            [
                "MODE #eu-opers +l :10\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#eu-opers',
                        'mode' => '+l',
                        'params' => '10',
                        'limit' => '10',
                        'all' => '#eu-opers +l :10',
                    ],
                    'targets' => ['#eu-opers'],
                ],
            ],

            [
                "MODE #eu-opers +lL :10 #eu-opers-overflow\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#eu-opers',
                        'mode' => '+lL',
                        'params' => '10 #eu-opers-overflow',
                        'all' => '#eu-opers +lL :10 #eu-opers-overflow',
                    ],
                    'targets' => ['#eu-opers'],
                ],
            ],

            [
                "MODE &oulu :+b\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '&oulu',
                        'mode' => '+b',
                        'all' => '&oulu :+b',
                    ],
                    'targets' => ['&oulu'],
                ],
            ],

            [
                "MODE &oulu +b :*!*@*\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '&oulu',
                        'mode' => '+b',
                        'params' => '*!*@*',
                        'banmask' => '*!*@*',
                        'all' => '&oulu +b :*!*@*',
                    ],
                    'targets' => ['&oulu'],
                ],
            ],

            [
                "MODE &oulu +b :*!*@*.edu\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '&oulu',
                        'mode' => '+b',
                        'params' => '*!*@*.edu',
                        'banmask' => '*!*@*.edu',
                        'all' => '&oulu +b :*!*@*.edu',
                    ],
                    'targets' => ['&oulu'],
                ],
            ],

            [
                "MODE &oulu +b-b :*!*@*.edu *!*@*.ac.uk\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '&oulu',
                        'mode' => '+b-b',
                        'params' => '*!*@*.edu *!*@*.ac.uk',
                        'all' => '&oulu +b-b :*!*@*.edu *!*@*.ac.uk',
                    ],
                    'targets' => ['&oulu'],
                ],
            ],

            [
                "MODE Wiz :-w\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'user' => 'Wiz',
                        'mode' => '-w',
                        'all' => 'Wiz :-w',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            [
                ":Angel MODE Angel :+i\r\n",
                [
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'MODE',
                    'params' => [
                        'user' => 'Angel',
                        'mode' => '+i',
                        'all' => 'Angel :+i',
                    ],
                    'targets' => ['Angel'],
                ],
            ],

            [
                "MODE Wiz :-o\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'user' => 'Wiz',
                        'mode' => '-o',
                        'all' => 'Wiz :-o',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            [
                "MODE Kilroy +s :+CcQq\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'user' => 'Kilroy',
                        'mode' => '+s',
                        'params' => '+CcQq',
                        'all' => 'Kilroy +s :+CcQq',
                    ],
                    'targets' => ['Kilroy'],
                ],
            ],

            [
                "MODE Angel +ws :+CcQq\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'user' => 'Angel',
                        'mode' => '+ws',
                        'params' => '+CcQq',
                        'all' => 'Angel +ws :+CcQq',
                    ],
                    'targets' => ['Angel'],
                ],
            ],

            [
                "MODE :#channel\r\n",
                [
                    'command' => 'MODE',
                    'params' => [
                        'channel' => '#channel',
                        'all' => ':#channel',
                    ],
                    'targets' => ['#channel'],
                ],
            ],

            // TOPIC (RFC 1459 Section 4.2.4)
            [
                ":Wiz TOPIC #test :New topic\r\n",
                [
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'TOPIC',
                    'params' => [
                        'channel' => '#test',
                        'topic' => 'New topic',
                        'all' => '#test :New topic',
                    ],
                    'targets' => ['#test'],
                ],
            ],

            [
                "TOPIC #test :another topic\r\n",
                [
                    'command' => 'TOPIC',
                    'params' => [
                        'channel' => '#test',
                        'topic' => 'another topic',
                        'all' => '#test :another topic',
                    ],
                    'targets' => ['#test'],
                ],
            ],

            [
                "TOPIC :#test\r\n",
                [
                    'command' => 'TOPIC',
                    'params' => [
                        'channel' => '#test',
                        'all' => ':#test',
                    ],
                    'targets' => ['#test'],
                ],
            ],

            // NAMES (RFC 1459 Section 4.2.5)
            [
                "NAMES :#twilight_zone,#42\r\n",
                [
                    'command' => 'NAMES',
                    'params' => [
                        'channels' => '#twilight_zone,#42',
                        'all' => ':#twilight_zone,#42',
                    ],
                    'targets' => ['#twilight_zone', '#42'],
                ],
            ],

            [
                "NAMES\r\n",
                [
                    'command' => 'NAMES',
                ],
            ],

            // LIST (RFC 1459 Section 4.2.6)
            [
                "LIST\r\n",
                [
                    'command' => 'LIST',
                ],
            ],

            [
                "LIST :#twilight_zone,#42\r\n",
                [
                    'command' => 'LIST',
                    'params' => [
                        'channels' => '#twilight_zone,#42',
                        'all' => ':#twilight_zone,#42',
                    ],
                    'targets' => ['#twilight_zone', '#42'],
                ],
            ],

            // INVITE (RFC 1459 Section 4.2.7)
            [
                ":Angel INVITE Wiz :#Dust\r\n",
                [
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'INVITE',
                    'params' => [
                        'nickname' => 'Wiz',
                        'channel' => '#Dust',
                        'all' => 'Wiz :#Dust',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            [
                "INVITE Wiz :#Twilight_Zone\r\n",
                [
                    'command' => 'INVITE',
                    'params' => [
                        'nickname' => 'Wiz',
                        'channel' => '#Twilight_Zone',
                        'all' => 'Wiz :#Twilight_Zone',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            // KICK (RFC 1459 Section 4.2.8)
            [
                "KICK &Melbourne :Matthew\r\n",
                [
                    'command' => 'KICK',
                    'params' => [
                        'channel' => '&Melbourne',
                        'user' => 'Matthew',
                        'all' => '&Melbourne :Matthew',
                    ],
                    'targets' => ['&Melbourne'],
                ],
            ],

            [
                "KICK #Finnish John :Speaking English\r\n",
                [
                    'command' => 'KICK',
                    'params' => [
                        'channel' => '#Finnish',
                        'user' => 'John',
                        'comment' => 'Speaking English',
                        'all' => '#Finnish John :Speaking English',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            [
                ":WiZ KICK #Finnish :John\r\n",
                [
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'KICK',
                    'params' => [
                        'channel' => '#Finnish',
                        'user' => 'John',
                        'all' => '#Finnish :John',
                    ],
                    'targets' => ['#Finnish'],
                ],
            ],

            // VERSION (RFC 1459 Section 4.3.1)
            [
                ":Wiz VERSION :*.se\r\n",
                [
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'VERSION',
                    'params' => [
                        'server' => '*.se',
                        'all' => ':*.se',
                    ],
                ],
            ],

            [
                "VERSION :tolsun.oulu.fi\r\n",
                [
                    'command' => 'VERSION',
                    'params' => [
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ],
                ],
            ],

            // STATS (RFC 1459 Section 4.3.2)
            [
                "STATS :m\r\n",
                [
                    'command' => 'STATS',
                    'params' => [
                        'query' => 'm',
                        'all' => ':m',
                    ],
                    'targets' => ['m'],
                ],
            ],

            [
                ":Wiz STATS c :eff.org\r\n",
                [
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'STATS',
                    'params' => [
                        'query' => 'c',
                        'server' => 'eff.org',
                        'all' => 'c :eff.org',
                    ],
                    'targets' => ['c'],
                ],
            ],

            // LINKS (RFC 1459 Section 4.3.3)
            [
                "LINKS :*.au\r\n",
                [
                    'command' => 'LINKS',
                    'params' => [
                        'servermask' => '*.au',
                        'all' => ':*.au',
                    ],
                ],
            ],

            [
                ":WiZ LINKS *.bu.edu :*.edu\r\n",
                [
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'LINKS',
                    'params' => [
                        'remoteserver' => '*.bu.edu',
                        'servermask' => '*.edu',
                        'all' => '*.bu.edu :*.edu',
                    ],
                ],
            ],

            // TIME (RFC 1459 Section 4.3.4)
            [
                "TIME :tolsun.oulu.fi\r\n",
                [
                    'command' => 'TIME',
                    'params' => [
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ],
                ],
            ],

            [
                ":Angel TIME :*.au\r\n",
                [
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'TIME',
                    'params' => [
                        'server' => '*.au',
                        'all' => ':*.au',
                    ],
                ],
            ],

            // CONNECT (RFC 1459 Section 4.3.5)
            [
                "CONNECT :tolsun.oulu.fi\r\n",
                [
                    'command' => 'CONNECT',
                    'params' => [
                        'targetserver' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ],
                ],
            ],

            [
                ":WiZ CONNECT eff.org 6667 :csd.bu.edu\r\n",
                [
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'CONNECT',
                    'params' => [
                        'targetserver' => 'eff.org',
                        'port' => '6667',
                        'remoteserver' => 'csd.bu.edu',
                        'all' => 'eff.org 6667 :csd.bu.edu',
                    ],
                ],
            ],

            // TRACE (RFC 1459 Section 4.3.6)
            [
                "TRACE :*.oulu.fi\r\n",
                [
                    'command' => 'TRACE',
                    'params' => [
                        'server' => '*.oulu.fi',
                        'all' => ':*.oulu.fi',
                    ],
                ],
            ],

            [
                ":WiZ TRACE :AngelDust\r\n",
                [
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'TRACE',
                    'params' => [
                        'server' => 'AngelDust',
                        'all' => ':AngelDust',
                    ],
                    'targets' => ['AngelDust'],
                ],
            ],

            // ADMIN (RFC 1459 Section 4.3.7)
            [
                "ADMIN :tolsun.oulu.fi\r\n",
                [
                    'command' => 'ADMIN',
                    'params' => [
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ],
                ],
            ],

            [
                ":WiZ ADMIN :*.edu\r\n",
                [
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'ADMIN',
                    'params' => [
                        'server' => '*.edu',
                        'all' => ':*.edu',
                    ],
                ],
            ],

            // INFO (RFC 1459 Section 4.3.8)
            [
                "INFO :csd.bu.edu\r\n",
                [
                    'command' => 'INFO',
                    'params' => [
                        'server' => 'csd.bu.edu',
                        'all' => ':csd.bu.edu',
                    ],
                ],
            ],

            [
                ":Avalon INFO :*.fi\r\n",
                [
                    'prefix' => ':Avalon',
                    'nick' => 'Avalon',
                    'command' => 'INFO',
                    'params' => [
                        'server' => '*.fi',
                        'all' => ':*.fi',
                    ],
                ],
            ],

            [
                "INFO :Angel\r\n",
                [
                    'command' => 'INFO',
                    'params' => [
                        'server' => 'Angel',
                        'all' => ':Angel',
                    ],
                    'targets' => ['Angel'],
                ],
            ],

            // PRIVMSG (RFC 1459 Section 4.4.1)
            [
                ":Angel PRIVMSG Wiz :Hello are you receiving this message ?\r\n",
                [
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'Wiz',
                        'text' => 'Hello are you receiving this message ?',
                        'all' => 'Wiz :Hello are you receiving this message ?',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            // Test nicks with ~
            [
                ":Angel~ PRIVMSG Wiz~ :Hello are you receiving this message ?\r\n",
                [
                    'prefix' => ':Angel~',
                    'nick' => 'Angel~',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'Wiz~',
                        'text' => 'Hello are you receiving this message ?',
                        'all' => 'Wiz~ :Hello are you receiving this message ?',
                    ],
                    'targets' => ['Wiz~'],
                ],
            ],

            [
                "PRIVMSG Angel :yes I'm receiving it !receiving it !'u>(768u+1n) .br\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'Angel',
                        'text' => 'yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                        'all' => 'Angel :yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                    ],
                    'targets' => ['Angel'],
                ],
            ],

            [
                "PRIVMSG jto@tolsun.oulu.fi :Hello !\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'jto@tolsun.oulu.fi',
                        'text' => 'Hello !',
                        'all' => 'jto@tolsun.oulu.fi :Hello !',
                    ],
                    'targets' => ['jto@tolsun.oulu.fi'],
                ],
            ],

            [
                "PRIVMSG $*.fi :Server tolsun.oulu.fi rebooting.\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => '$*.fi',
                        'text' => 'Server tolsun.oulu.fi rebooting.',
                        'all' => '$*.fi :Server tolsun.oulu.fi rebooting.',
                    ],
                    'targets' => ['$*.fi'],
                ],
            ],

            [
                "PRIVMSG #*.edu :NSFNet is undergoing work, expect interruptions\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => '#*.edu',
                        'text' => 'NSFNet is undergoing work, expect interruptions',
                        'all' => '#*.edu :NSFNet is undergoing work, expect interruptions',
                    ],
                    'targets' => ['#*.edu'],
                ],
            ],

            [
                ":foobar1!foobar2@foobar3.user.network PRIVMSG #channel :hi all\r\n",
                [
                    'prefix' => ':foobar1!foobar2@foobar3.user.network',
                    'nick' => 'foobar1',
                    'user' => 'foobar2',
                    'host' => 'foobar3.user.network',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => '#channel',
                        'text' => 'hi all',
                        'all' => '#channel :hi all',
                    ],
                    'targets' => ['#channel'],
                ],
            ],

            [
                ":foo_bar1!foo_bar2@foo_bar3.user.network PRIVMSG #channel :hi all\r\n",
                [
                    'prefix' => ':foo_bar1!foo_bar2@foo_bar3.user.network',
                    'nick' => 'foo_bar1',
                    'user' => 'foo_bar2',
                    'host' => 'foo_bar3.user.network',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => '#channel',
                        'text' => 'hi all',
                        'all' => '#channel :hi all',
                    ],
                    'targets' => ['#channel'],
                ],
            ],

            // NOTE: Because of syntactic equivalence, data sets for NOTICE
            // (RFC 1459 Section 4.4.2) equivalent to those for PRIVMSG are
            // derived later in this method rather than being duplicated here

            // WHO (RFC 1459 Section 4.5.1)
            [
                "WHO :*.fi\r\n",
                [
                    'command' => 'WHO',
                    'params' => [
                        'name' => '*.fi',
                        'all' => ':*.fi',
                    ],
                ],
            ],

            [
                "WHO jto* :o\r\n",
                [
                    'command' => 'WHO',
                    'params' => [
                        'name' => 'jto*',
                        'o' => 'o',
                        'all' => 'jto* :o',
                    ],
                ],
            ],

            // WHOIS (RFC 1459 Section 4.5.2)
            [
                "WHOIS :wiz\r\n",
                [
                    'command' => 'WHOIS',
                    'params' => [
                        'nickmasks' => 'wiz',
                        'all' => ':wiz',
                    ],
                ],
            ],

            [
                "WHOIS eff.org :Trillian\r\n",
                [
                    'command' => 'WHOIS',
                    'params' => [
                        'server' => 'eff.org',
                        'nickmasks' => 'Trillian',
                        'all' => 'eff.org :Trillian',
                    ],
                ],
            ],

            // WHOWAS (RFC 1459 Section 4.5.3)
            [
                "WHOWAS :Wiz\r\n",
                [
                    'command' => 'WHOWAS',
                    'params' => [
                        'nickname' => 'Wiz',
                        'all' => ':Wiz',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            [
                "WHOWAS Mermaid :9\r\n",
                [
                    'command' => 'WHOWAS',
                    'params' => [
                        'nickname' => 'Mermaid',
                        'count' => '9',
                        'all' => 'Mermaid :9',
                    ],
                    'targets' => ['Mermaid'],
                ],
            ],

            [
                "WHOWAS Trillian 1 :*.edu\r\n",
                [
                    'command' => 'WHOWAS',
                    'params' => [
                        'nickname' => 'Trillian',
                        'count' => '1',
                        'server' => '*.edu',
                        'all' => 'Trillian 1 :*.edu',
                    ],
                    'targets' => ['Trillian'],
                ],
            ],

            // KILL (RFC 1459 Section 4.6.1)
            [
                "KILL David :(csd.bu.edu <- tolsun.oulu.fi)\r\n",
                [
                    'command' => 'KILL',
                    'params' => [
                        'nickname' => 'David',
                        'comment' => '(csd.bu.edu <- tolsun.oulu.fi)',
                        'all' => 'David :(csd.bu.edu <- tolsun.oulu.fi)',
                    ],
                    'targets' => ['David'],
                ],
            ],

            // PING (RFC 1459 Section 4.6.2)
            [
                "PING :tolsun.oulu.fi\r\n",
                [
                    'command' => 'PING',
                    'params' => [
                        'server1' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ],
                ],
            ],

            [
                "PING :WiZ\r\n",
                [
                    'command' => 'PING',
                    'params' => [
                        'server1' => 'WiZ',
                        'all' => ':WiZ',
                    ],
                    'targets' => ['WiZ'],
                ],
            ],

            // PONG (RFC 1459 Section 4.6.3)
            [
                "PONG csd.bu.edu :tolsun.oulu.fi\r\n",
                [
                    'command' => 'PONG',
                    'params' => [
                        'daemon' => 'csd.bu.edu',
                        'daemon2' => 'tolsun.oulu.fi',
                        'all' => 'csd.bu.edu :tolsun.oulu.fi',
                    ],
                ],
            ],

            // ERROR (RFC 1459 Section 4.6.4)
            [
                "ERROR :Server *.fi already exists\r\n",
                [
                    'command' => 'ERROR',
                    'params' => [
                        'message' => 'Server *.fi already exists',
                        'all' => ':Server *.fi already exists',
                    ],
                ],
            ],

            [
                "NOTICE WiZ :ERROR from csd.bu.edu -- Server *.fi already exists\r\n",
                [
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'WiZ',
                        'text' => 'ERROR from csd.bu.edu -- Server *.fi already exists',
                        'all' => 'WiZ :ERROR from csd.bu.edu -- Server *.fi already exists',
                    ],
                    'targets' => ['WiZ'],
                ],
            ],

            // AWAY (RFC 1459 Section 5.1)
            [
                "AWAY :Gone to lunch.\r\n",
                [
                    'command' => 'AWAY',
                    'params' => [
                        'message' => 'Gone to lunch.',
                        'all' => ':Gone to lunch.',
                    ],
                ],
            ],

            [
                ":Wiz AWAY\r\n",
                [
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'AWAY',
                ],
            ],

            // REHASH (RFC 1459 Section 5.2)
            [
                "REHASH\r\n",
                [
                    'command' => 'REHASH',
                ],
            ],

            // RESTART (RFC 1459 Section 5.3)
            [
                "RESTART\r\n",
                [
                    'command' => 'RESTART',
                ],
            ],

            // SUMMON (RFC 1459 Section 5.4)
            [
                "SUMMON :jto\r\n",
                [
                    'command' => 'SUMMON',
                    'params' => [
                        'user' => 'jto',
                        'all' => ':jto',
                    ],
                    'targets' => ['jto'],
                ],
            ],

            [
                "SUMMON jto :tolsun.oulu.fi\r\n",
                [
                    'command' => 'SUMMON',
                    'params' => [
                        'user' => 'jto',
                        'server' => 'tolsun.oulu.fi',
                        'all' => 'jto :tolsun.oulu.fi',
                    ],
                    'targets' => ['jto'],
                ],
            ],

            // USERS (RFC 1459 Section 5.5)
            [
                "USERS :eff.org\r\n",
                [
                    'command' => 'USERS',
                    'params' => [
                        'server' => 'eff.org',
                        'all' => ':eff.org',
                    ],
                ],
            ],

            [
                ":John USERS :tolsun.oulu.fi\r\n",
                [
                    'prefix' => ':John',
                    'nick' => 'John',
                    'command' => 'USERS',
                    'params' => [
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ],
                ],
            ],

            // WALLOPS (RFC 1459 Section 5.6)
            [
                ":csd.bu.edu WALLOPS :Connect '*.uiuc.edu 6667' from Joshua\r\n",
                [
                    'prefix' => ':csd.bu.edu',
                    'servername' => 'csd.bu.edu',
                    'command' => 'WALLOPS',
                    'params' => [
                        'text' => 'Connect \'*.uiuc.edu 6667\' from Joshua',
                        'all' => ':Connect \'*.uiuc.edu 6667\' from Joshua',
                    ],
                ],
            ],

            // USERHOST (RFC 1459 Section 5.7)
            [
                "USERHOST Wiz Michael Marty :p\r\n",
                [
                    'command' => 'USERHOST',
                    'params' => [
                        'nickname1' => 'Wiz',
                        'nickname2' => 'Michael',
                        'nickname3' => 'Marty',
                        'nickname4' => 'p',
                        'all' => 'Wiz Michael Marty :p',
                    ],
                    'targets' => ['Wiz'],
                ],
            ],

            // ISON (RFC 1459 Section 5.8)
            [
                "ISON :phone trillian WiZ jarlek Avalon Angel Monstah\r\n",
                [
                    'command' => 'ISON',
                    'params' => [
                        'nicknames' => 'phone trillian WiZ jarlek Avalon Angel Monstah',
                        'all' => ':phone trillian WiZ jarlek Avalon Angel Monstah',
                    ],
                ],
            ],

            // PROTOCTL
            [
                "PROTOCTL NAMESX\r\n",
                [
                    'command' => 'PROTOCTL',
                    'params' => [
                        'proto' => 'NAMESX',
                        'all' => 'NAMESX',
                    ],
                ],
            ],

            // Error replies (RFC 1459 Section 6.1)
            [
                "401\r\n",
                [
                    'command' => '401',
                    'code' => 'ERR_NOSUCHNICK',
                ],
            ],
            [
                "402\r\n",
                [
                    'command' => '402',
                    'code' => 'ERR_NOSUCHSERVER',
                ],
            ],
            [
                "403\r\n",
                [
                    'command' => '403',
                    'code' => 'ERR_NOSUCHCHANNEL',
                ],
            ],
            [
                "404\r\n",
                [
                    'command' => '404',
                    'code' => 'ERR_CANNOTSENDTOCHAN',
                ],
            ],
            [
                "405\r\n",
                [
                    'command' => '405',
                    'code' => 'ERR_TOOMANYCHANNELS',
                ],
            ],
            [
                "406\r\n",
                [
                    'command' => '406',
                    'code' => 'ERR_WASNOSUCHNICK',
                ],
            ],
            [
                "407\r\n",
                [
                    'command' => '407',
                    'code' => 'ERR_TOOMANYTARGETS',
                ],
            ],
            [
                "409\r\n",
                [
                    'command' => '409',
                    'code' => 'ERR_NOORIGIN',
                ],
            ],
            [
                "411\r\n",
                [
                    'command' => '411',
                    'code' => 'ERR_NORECIPIENT',
                ],
            ],
            [
                "412\r\n",
                [
                    'command' => '412',
                    'code' => 'ERR_NOTEXTTOSEND',
                ],
            ],
            [
                "413\r\n",
                [
                    'command' => '413',
                    'code' => 'ERR_NOTOPLEVEL',
                ],
            ],
            [
                "414\r\n",
                [
                    'command' => '414',
                    'code' => 'ERR_WILDTOPLEVEL',
                ],
            ],
            [
                "421\r\n",
                [
                    'command' => '421',
                    'code' => 'ERR_UNKNOWNCOMMAND',
                ],
            ],
            [
                "422\r\n",
                [
                    'command' => '422',
                    'code' => 'ERR_NOMOTD',
                ],
            ],
            [
                "423\r\n",
                [
                    'command' => '423',
                    'code' => 'ERR_NOADMININFO',
                ],
            ],
            [
                "424\r\n",
                [
                    'command' => '424',
                    'code' => 'ERR_FILEERROR',
                ],
            ],
            [
                "431\r\n",
                [
                    'command' => '431',
                    'code' => 'ERR_NONICKNAMEGIVEN',
                ],
            ],
            [
                "432\r\n",
                [
                    'command' => '432',
                    'code' => 'ERR_ERRONEUSNICKNAME',
                ],
            ],
            [
                "433\r\n",
                [
                    'command' => '433',
                    'code' => 'ERR_NICKNAMEINUSE',
                ],
            ],
            [
                "436\r\n",
                [
                    'command' => '436',
                    'code' => 'ERR_NICKCOLLISION',
                ],
            ],
            [
                "441\r\n",
                [
                    'command' => '441',
                    'code' => 'ERR_USERNOTINCHANNEL',
                ],
            ],
            [
                "442\r\n",
                [
                    'command' => '442',
                    'code' => 'ERR_NOTONCHANNEL',
                ],
            ],
            [
                "443\r\n",
                [
                    'command' => '443',
                    'code' => 'ERR_USERONCHANNEL',
                ],
            ],
            [
                "444\r\n",
                [
                    'command' => '444',
                    'code' => 'ERR_NOLOGIN',
                ],
            ],
            [
                "445\r\n",
                [
                    'command' => '445',
                    'code' => 'ERR_SUMMONDISABLED',
                ],
            ],
            [
                "446\r\n",
                [
                    'command' => '446',
                    'code' => 'ERR_USERSDISABLED',
                ],
            ],
            [
                "451\r\n",
                [
                    'command' => '451',
                    'code' => 'ERR_NOTREGISTERED',
                ],
            ],
            [
                "461\r\n",
                [
                    'command' => '461',
                    'code' => 'ERR_NEEDMOREPARAMS',
                ],
            ],
            [
                "462\r\n",
                [
                    'command' => '462',
                    'code' => 'ERR_ALREADYREGISTRED',
                ],
            ],
            [
                "463\r\n",
                [
                    'command' => '463',
                    'code' => 'ERR_NOPERMFORHOST',
                ],
            ],
            [
                "464\r\n",
                [
                    'command' => '464',
                    'code' => 'ERR_PASSWDMISMATCH',
                ],
            ],
            [
                "465\r\n",
                [
                    'command' => '465',
                    'code' => 'ERR_YOUREBANNEDCREEP',
                ],
            ],
            [
                "467\r\n",
                [
                    'command' => '467',
                    'code' => 'ERR_KEYSET',
                ],
            ],
            [
                "471\r\n",
                [
                    'command' => '471',
                    'code' => 'ERR_CHANNELISFULL',
                ],
            ],
            [
                "472\r\n",
                [
                    'command' => '472',
                    'code' => 'ERR_UNKNOWNMODE',
                ],
            ],
            [
                "473\r\n",
                [
                    'command' => '473',
                    'code' => 'ERR_INVITEONLYCHAN',
                ],
            ],
            [
                "474\r\n",
                [
                    'command' => '474',
                    'code' => 'ERR_BANNEDFROMCHAN',
                ],
            ],
            [
                "475\r\n",
                [
                    'command' => '475',
                    'code' => 'ERR_BADCHANNELKEY',
                ],
            ],
            [
                "481\r\n",
                [
                    'command' => '481',
                    'code' => 'ERR_NOPRIVILEGES',
                ],
            ],
            [
                "482\r\n",
                [
                    'command' => '482',
                    'code' => 'ERR_CHANOPRIVSNEEDED',
                ],
            ],
            [
                "483\r\n",
                [
                    'command' => '483',
                    'code' => 'ERR_CANTKILLSERVER',
                ],
            ],
            [
                "491\r\n",
                [
                    'command' => '491',
                    'code' => 'ERR_NOOPERHOST',
                ],
            ],
            [
                "501\r\n",
                [
                    'command' => '501',
                    'code' => 'ERR_UMODEUNKNOWNFLAG',
                ],
            ],
            [
                "502\r\n",
                [
                    'command' => '502',
                    'code' => 'ERR_USERSDONTMATCH',
                ],
            ],

            // Command responses (RFC 1459 Section 6.2)
            [
                "300\r\n",
                [
                    'command' => '300',
                    'code' => 'RPL_NONE',
                ],
            ],
            [
                "302\r\n",
                [
                    'command' => '302',
                    'code' => 'RPL_USERHOST',
                ],
            ],
            [
                "303\r\n",
                [
                    'command' => '303',
                    'code' => 'RPL_ISON',
                ],
            ],
            [
                "301\r\n",
                [
                    'command' => '301',
                    'code' => 'RPL_AWAY',
                ],
            ],
            [
                "305\r\n",
                [
                    'command' => '305',
                    'code' => 'RPL_UNAWAY',
                ],
            ],
            [
                "306\r\n",
                [
                    'command' => '306',
                    'code' => 'RPL_NOWAWAY',
                ],
            ],
            [
                "311\r\n",
                [
                    'command' => '311',
                    'code' => 'RPL_WHOISUSER',
                ],
            ],
            [
                "312\r\n",
                [
                    'command' => '312',
                    'code' => 'RPL_WHOISSERVER',
                ],
            ],
            [
                "313\r\n",
                [
                    'command' => '313',
                    'code' => 'RPL_WHOISOPERATOR',
                ],
            ],
            [
                "317\r\n",
                [
                    'command' => '317',
                    'code' => 'RPL_WHOISIDLE',
                ],
            ],
            [
                "318\r\n",
                [
                    'command' => '318',
                    'code' => 'RPL_ENDOFWHOIS',
                ],
            ],
            [
                "319\r\n",
                [
                    'command' => '319',
                    'code' => 'RPL_WHOISCHANNELS',
                ],
            ],
            [
                "314\r\n",
                [
                    'command' => '314',
                    'code' => 'RPL_WHOWASUSER',
                ],
            ],
            [
                "369\r\n",
                [
                    'command' => '369',
                    'code' => 'RPL_ENDOFWHOWAS',
                ],
            ],
            [
                "321\r\n",
                [
                    'command' => '321',
                    'code' => 'RPL_LISTSTART',
                ],
            ],
            [
                "322\r\n",
                [
                    'command' => '322',
                    'code' => 'RPL_LIST',
                ],
            ],
            [
                "323\r\n",
                [
                    'command' => '323',
                    'code' => 'RPL_LISTEND',
                ],
            ],
            [
                "324\r\n",
                [
                    'command' => '324',
                    'code' => 'RPL_CHANNELMODEIS',
                ],
            ],
            [
                "331\r\n",
                [
                    'command' => '331',
                    'code' => 'RPL_NOTOPIC',
                ],
            ],
            [
                "332\r\n",
                [
                    'command' => '332',
                    'code' => 'RPL_TOPIC',
                ],
            ],
            [
                "341\r\n",
                [
                    'command' => '341',
                    'code' => 'RPL_INVITING',
                ],
            ],
            [
                "342\r\n",
                [
                    'command' => '342',
                    'code' => 'RPL_SUMMONING',
                ],
            ],
            [
                "351\r\n",
                [
                    'command' => '351',
                    'code' => 'RPL_VERSION',
                ],
            ],
            [
                "352\r\n",
                [
                    'command' => '352',
                    'code' => 'RPL_WHOREPLY',
                ],
            ],
            [
                "315\r\n",
                [
                    'command' => '315',
                    'code' => 'RPL_ENDOFWHO',
                ],
            ],
            [
                "353\r\n",
                [
                    'command' => '353',
                    'code' => 'RPL_NAMREPLY',
                ],
            ],
            [
                "366\r\n",
                [
                    'command' => '366',
                    'code' => 'RPL_ENDOFNAMES',
                ],
            ],
            [
                "364\r\n",
                [
                    'command' => '364',
                    'code' => 'RPL_LINKS',
                ],
            ],
            [
                "365\r\n",
                [
                    'command' => '365',
                    'code' => 'RPL_ENDOFLINKS',
                ],
            ],
            [
                "367\r\n",
                [
                    'command' => '367',
                    'code' => 'RPL_BANLIST',
                ],
            ],
            [
                "368\r\n",
                [
                    'command' => '368',
                    'code' => 'RPL_ENDOFBANLIST',
                ],
            ],
            [
                "371\r\n",
                [
                    'command' => '371',
                    'code' => 'RPL_INFO',
                ],
            ],
            [
                "374\r\n",
                [
                    'command' => '374',
                    'code' => 'RPL_ENDOFINFO',
                ],
            ],
            [
                "375\r\n",
                [
                    'command' => '375',
                    'code' => 'RPL_MOTDSTART',
                ],
            ],
            [
                "372\r\n",
                [
                    'command' => '372',
                    'code' => 'RPL_MOTD',
                ],
            ],
            [
                "376\r\n",
                [
                    'command' => '376',
                    'code' => 'RPL_ENDOFMOTD',
                ],
            ],
            [
                "381\r\n",
                [
                    'command' => '381',
                    'code' => 'RPL_YOUREOPER',
                ],
            ],
            [
                "382\r\n",
                [
                    'command' => '382',
                    'code' => 'RPL_REHASHING',
                ],
            ],
            [
                "391\r\n",
                [
                    'command' => '391',
                    'code' => 'RPL_TIME',
                ],
            ],
            [
                "392\r\n",
                [
                    'command' => '392',
                    'code' => 'RPL_USERSSTART',
                ],
            ],
            [
                "393\r\n",
                [
                    'command' => '393',
                    'code' => 'RPL_USERS',
                ],
            ],
            [
                "394\r\n",
                [
                    'command' => '394',
                    'code' => 'RPL_ENDOFUSERS',
                ],
            ],
            [
                "395\r\n",
                [
                    'command' => '395',
                    'code' => 'RPL_NOUSERS',
                ],
            ],
            [
                "200\r\n",
                [
                    'command' => '200',
                    'code' => 'RPL_TRACELINK',
                ],
            ],
            [
                "201\r\n",
                [
                    'command' => '201',
                    'code' => 'RPL_TRACECONNECTING',
                ],
            ],
            [
                "202\r\n",
                [
                    'command' => '202',
                    'code' => 'RPL_TRACEHANDSHAKE',
                ],
            ],
            [
                "203\r\n",
                [
                    'command' => '203',
                    'code' => 'RPL_TRACEUNKNOWN',
                ],
            ],
            [
                "204\r\n",
                [
                    'command' => '204',
                    'code' => 'RPL_TRACEOPERATOR',
                ],
            ],
            [
                "205\r\n",
                [
                    'command' => '205',
                    'code' => 'RPL_TRACEUSER',
                ],
            ],
            [
                "206\r\n",
                [
                    'command' => '206',
                    'code' => 'RPL_TRACESERVER',
                ],
            ],
            [
                "208\r\n",
                [
                    'command' => '208',
                    'code' => 'RPL_TRACENEWTYPE',
                ],
            ],
            [
                "261\r\n",
                [
                    'command' => '261',
                    'code' => 'RPL_TRACELOG',
                ],
            ],
            [
                "211\r\n",
                [
                    'command' => '211',
                    'code' => 'RPL_STATSLINKINFO',
                ],
            ],
            [
                "212\r\n",
                [
                    'command' => '212',
                    'code' => 'RPL_STATSCOMMANDS',
                ],
            ],
            [
                "213\r\n",
                [
                    'command' => '213',
                    'code' => 'RPL_STATSCLINE',
                ],
            ],
            [
                "214\r\n",
                [
                    'command' => '214',
                    'code' => 'RPL_STATSNLINE',
                ],
            ],
            [
                "215\r\n",
                [
                    'command' => '215',
                    'code' => 'RPL_STATSILINE',
                ],
            ],
            [
                "216\r\n",
                [
                    'command' => '216',
                    'code' => 'RPL_STATSKLINE',
                ],
            ],
            [
                "218\r\n",
                [
                    'command' => '218',
                    'code' => 'RPL_STATSYLINE',
                ],
            ],
            [
                "219\r\n",
                [
                    'command' => '219',
                    'code' => 'RPL_ENDOFSTATS',
                ],
            ],
            [
                "241\r\n",
                [
                    'command' => '241',
                    'code' => 'RPL_STATSLLINE',
                ],
            ],
            [
                "242\r\n",
                [
                    'command' => '242',
                    'code' => 'RPL_STATSUPTIME',
                ],
            ],
            [
                "243\r\n",
                [
                    'command' => '243',
                    'code' => 'RPL_STATSOLINE',
                ],
            ],
            [
                "244\r\n",
                [
                    'command' => '244',
                    'code' => 'RPL_STATSHLINE',
                ],
            ],
            [
                "221\r\n",
                [
                    'command' => '221',
                    'code' => 'RPL_UMODEIS',
                ],
            ],
            [
                "251\r\n",
                [
                    'command' => '251',
                    'code' => 'RPL_LUSERCLIENT',
                ],
            ],
            [
                "252\r\n",
                [
                    'command' => '252',
                    'code' => 'RPL_LUSEROP',
                ],
            ],
            [
                "253\r\n",
                [
                    'command' => '253',
                    'code' => 'RPL_LUSERUNKNOWN',
                ],
            ],
            [
                "254\r\n",
                [
                    'command' => '254',
                    'code' => 'RPL_LUSERCHANNELS',
                ],
            ],
            [
                "255\r\n",
                [
                    'command' => '255',
                    'code' => 'RPL_LUSERME',
                ],
            ],
            [
                "256\r\n",
                [
                    'command' => '256',
                    'code' => 'RPL_ADMINME',
                ],
            ],
            [
                "257\r\n",
                [
                    'command' => '257',
                    'code' => 'RPL_ADMINLOC1',
                ],
            ],
            [
                "258\r\n",
                [
                    'command' => '258',
                    'code' => 'RPL_ADMINLOC2',
                ],
            ],
            [
                "259\r\n",
                [
                    'command' => '259',
                    'code' => 'RPL_ADMINEMAIL',
                ],
            ],
            [
                "999\r\n",
                [
                    'command' => '999',
                    'code' => '999',
                ],
            ],
            
            // ACTION (CTCP Specification)
            [
                ":john!~jsmith@example.com PRIVMSG #test :\001ACTION test\001\r\n",
                [
                    'prefix' => ':john!~jsmith@example.com',
                    'nick' => 'john',
                    'user' => '~jsmith',
                    'host' => 'example.com',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'all' => "#test :\001ACTION test\001",
                        'receivers' => '#test',
                        'text' => "\001ACTION test\001",
                    ],
                    'message' => ":john!~jsmith@example.com PRIVMSG #test :\001ACTION test\001\r\n",
                    'targets' => [
                        '0' => '#test',
                    ],
                    'ctcp' => [
                        'command' => 'ACTION',
                        'params' => [
                            'all' => 'test',
                        ],
                    ],
                ],
            ],

            // FINGER (CTCP Specification)
            [
                "PRIVMSG victim :\001FINGER\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001FINGER\001",
                        'all' => "victim :\001FINGER\001",
                    ],
                    'targets' => ['victim'],
                    'ctcp' => [
                        'command' => 'FINGER',
                    ],
                ],
            ],

            [
                ":victim NOTICE actor :\001FINGER :Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.\001\r\n",
                [
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'actor',
                        'text' => "\001FINGER :Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.\001",
                        'all' => "actor :\001FINGER :Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.\001",
                    ],
                    'targets' => ['actor'],
                    'ctcp' => [
                        'command' => 'FINGER',
                        'params' => [
                            'user' => 'Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.',
                            'all' => ':Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.'
                        ],
                    ],
                ],
            ],

            // VERSION (CTCP Specification)
            [
                "PRIVMSG victim :\001VERSION\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001VERSION\001",
                        'all' => "victim :\001VERSION\001",
                    ],
                    'targets' => ['victim'],
                    'ctcp' => [
                        'command' => 'VERSION',
                    ],
                ],
            ],

            [
                ":victim NOTICE actor :\001VERSION Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README\001\r\n",
                [
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'actor',
                        'text' => "\001VERSION Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README\001",
                        'all' => "actor :\001VERSION Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README\001",
                    ],
                    'targets' => ['actor'],
                    'ctcp' => [
                        'command' => 'VERSION',
                        'params' => [
                            'name' => 'Kiwi',
                            'version' => '5.2',
                            'environment' => 'GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README',
                            'all' => 'Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README',
                        ],
                    ],
                ]
            ],

            // SOURCE (CTCP Specification)
            [
                "PRIVMSG victim :\001SOURCE cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001SOURCE cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z\001",
                        'all' => "victim :\001SOURCE cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z\001",
                    ],
                    'targets' => ['victim'],
                    'ctcp' => [
                        'command' => 'SOURCE',
                        'params' => [
                            'host' => 'cs.bu.edu',
                            'directories' => '/pub/irc',
                            'files' => 'Kiwi.5.2.el.Z',
                            'all' => 'cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z',
                        ],
                    ],
                ],
            ],

            // USERINFO (CTCP Specification)
            [
                "PRIVMSG victim :\001USERINFO\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001USERINFO\001",
                        'all' => "victim :\001USERINFO\001",
                    ],
                    'targets' => ['victim'],
                    'ctcp' => [
                        'command' => 'USERINFO',
                    ],
                ],
            ],

            [
                ":victim NOTICE actor :\001USERINFO :I'm studying computer science in Uppsala, I'm male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.\001\r\n",
                [
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'actor',
                        'text' => "\001USERINFO :I'm studying computer science in Uppsala, I'm male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.\001",
                        'all' => "actor :\001USERINFO :I'm studying computer science in Uppsala, I'm male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.\001",
                    ],
                    'targets' => ['actor'],
                    'ctcp' => [
                        'command' => 'USERINFO',
                        'params' => [
                            'user' => 'I\'m studying computer science in Uppsala, I\'m male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.',
                            'all' => ':I\'m studying computer science in Uppsala, I\'m male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.',
                        ],
                    ],
                ],
            ],

            // CLIENTINFO (CTCP Specification)
            [
                "PRIVMSG victim :\001CLIENTINFO\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001CLIENTINFO\001",
                        'all' => "victim :\001CLIENTINFO\001",
                    ],
                    'targets' => ['victim'],
                    'ctcp' => [
                        'command' => 'CLIENTINFO',
                    ],
                ],
            ],

            [
                ":victim NOTICE actor :\001CLIENTINFO :You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.\001\r\n",
                [
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'actor',
                        'text' => "\001CLIENTINFO :You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.\001",
                        'all' => "actor :\001CLIENTINFO :You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.\001",
                    ],
                    'targets' => ['actor'],
                    'ctcp' => [
                        'command' => 'CLIENTINFO',
                        'params' => [
                            'client' => 'You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.',
                            'all' => ':You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.',
                        ],
                    ],
                ],
            ],

            // ERRMSG (CTCP Specification)
            [
                ":victim NOTICE actor :\001ERRMSG clientinfo clientinfo :Query is unknown\001\r\n",
                [
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'actor',
                        'text' => "\001ERRMSG clientinfo clientinfo :Query is unknown\001",
                        'all' => "actor :\001ERRMSG clientinfo clientinfo :Query is unknown\001",
                    ],
                    'targets' => ['actor'],
                    'ctcp' => [
                        'command' => 'ERRMSG',
                        'params' => [
                            'query' => 'clientinfo clientinfo',
                            'message' => 'Query is unknown',
                            'all' => 'clientinfo clientinfo :Query is unknown',
                        ],
                    ],
                ],
            ],

            // PING (CTCP Specification)
            [
                "PRIVMSG victim :\001PING 1350742705\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001PING 1350742705\001",
                        'all' => "victim :\001PING 1350742705\001",
                    ],
                    'targets' => ['victim'],
                    'ctcp' => [
                        'command' => 'PING',
                        'params' => [
                            'timestamp' => '1350742705',
                            'all' => '1350742705',
                        ],
                    ],
                ],
            ],

            [
                ":victim NOTICE actor :\001PING 1350742748\001\r\n",
                [
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'actor',
                        'text' => "\001PING 1350742748\001",
                        'all' => "actor :\001PING 1350742748\001",
                    ],
                    'targets' => ['actor'],
                    'ctcp' => [
                        'command' => 'PING',
                        'params' => [
                            'timestamp' => '1350742748',
                            'all' => '1350742748',
                        ],
                    ],
                ]
            ],

            // TIME (CTCP Specification)
            [
                "PRIVMSG victim :\001TIME\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001TIME\001",
                        'all' => "victim :\001TIME\001",
                    ],
                    'targets' => ['victim'],
                    'ctcp' => [
                        'command' => 'TIME',
                    ],
                ],
            ],

            [
                ":victim NOTICE actor :\001TIME :Thu Aug 11 22:52:51 1994 CST\001\r\n",
                [
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => [
                        'nickname' => 'actor',
                        'text' => "\001TIME :Thu Aug 11 22:52:51 1994 CST\001",
                        'all' => "actor :\001TIME :Thu Aug 11 22:52:51 1994 CST\001",
                    ],
                    'targets' => ['actor'],
                    'ctcp' => [
                        'command' => 'TIME',
                        'params' => [
                            'time' => 'Thu Aug 11 22:52:51 1994 CST',
                            'all' => ':Thu Aug 11 22:52:51 1994 CST',
                        ],
                    ],
                ]
            ],

            // Malformed CTCP command
            [
                "PRIVMSG victim :\001ACTIONlooks\001\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'victim',
                        'text' => "\001ACTIONlooks\001",
                        'all' => "victim :\001ACTIONlooks\001",
                    ],
                    'targets' => ['victim'],
                    'message' => "PRIVMSG victim :\001ACTIONlooks\001\r\n",
                    'ctcp' => [
                        'command' => 'ACTIONlooks'
                    ],
                ],
            ],

            // Individual NUL, CR, or LF characters are stripped out
            [
                ":server.name 372 BotNick :Who left a null byte \0 in here?\r\n",
                [
                    'prefix' => ':server.name',
                    'servername' => 'server.name',
                    'command' => '372',
                    'params' => [
                        1 => 'Who left a null byte  in here?',
                        'iterable' => [],
                        'tail' => 'Who left a null byte  in here?',
                        'all' => ':Who left a null byte  in here?',
                    ],
                    'code' => 'RPL_MOTD',
                    'target' => 'BotNick',
                    'message' => ":server.name 372 BotNick :Who left a null byte  in here?\r\n",
                ],
            ],

            [
                ":server.name 372 BotNick :Who left a carriage return \r in here?\r\n",
                [
                    'prefix' => ':server.name',
                    'servername' => 'server.name',
                    'command' => '372',
                    'params' => [
                        1 => 'Who left a carriage return  in here?',
                        'iterable' => [],
                        'tail' => 'Who left a carriage return  in here?',
                        'all' => ':Who left a carriage return  in here?',
                    ],
                    'code' => 'RPL_MOTD',
                    'target' => 'BotNick',
                    'message' => ":server.name 372 BotNick :Who left a carriage return  in here?\r\n",
                ],
            ],

            [
                ":server.name 372 BotNick :Who left a line feed \n in here?\r\n",
                [
                    'prefix' => ':server.name',
                    'servername' => 'server.name',
                    'command' => '372',
                    'params' => [
                        1 => 'Who left a line feed  in here?',
                        'iterable' => [],
                        'tail' => 'Who left a line feed  in here?',
                        'all' => ':Who left a line feed  in here?',
                    ],
                    'code' => 'RPL_MOTD',
                    'target' => 'BotNick',
                    'message' => ":server.name 372 BotNick :Who left a line feed  in here?\r\n",
                ],
            ],

            // Freenode doesn't properly demarcate trailing command parameters in some cases
            [
                ":pratchett.freenode.net 004 Phergie3 pratchett.freenode.net ircd-seven-1.1.3 DOQRSZaghilopswz CFILMPQbcefgijklmnopqrstvz bkloveqjfI\r\n",
                [
                    'prefix' => ':pratchett.freenode.net',
                    'servername' => 'pratchett.freenode.net',
                    'command' => '004',
                    'params' => [
                        1 => 'pratchett.freenode.net',
                        2 => 'ircd-seven-1.1.3',
                        3 => 'DOQRSZaghilopswz',
                        4 => 'CFILMPQbcefgijklmnopqrstvz',
                        5 => 'bkloveqjfI',
                        'iterable' => [
                            'pratchett.freenode.net',
                            'ircd-seven-1.1.3',
                            'DOQRSZaghilopswz',
                            'CFILMPQbcefgijklmnopqrstvz',
                            'bkloveqjfI',
                        ],
                        'all' => 'pratchett.freenode.net ircd-seven-1.1.3 DOQRSZaghilopswz CFILMPQbcefgijklmnopqrstvz bkloveqjfI',
                    ],
                    'code' => '004',
                    'target' => 'Phergie3',
                ],
            ],

            // Freenode uses an invalid hostname in some server responses
            [
                ":services. 328 Phergie3 #laravel :http://laravel.com\r\n",
                [
                    'prefix' => ':services.',
                    'servername' => 'services.',
                    'command' => '328',
                    'params' => [
                        1 => '#laravel',
                        2 => 'http://laravel.com',
                        'iterable' => ['#laravel'],
                        'tail' => 'http://laravel.com',
                        'all' => '#laravel :http://laravel.com',
                    ],
                    'message' => ":services. 328 Phergie3 #laravel :http://laravel.com\r\n",
                    'code' => '328',
                    'target' => 'Phergie3',
                ],
            ],

            // Freenode doesn't prefix the PART channels parameter with a colon
            [
                ":julien-c!~julien-c@tru75-6-82-240-32-161.fbx.proxad.net PART #laravel\r\n",
                [
                    'prefix' => ':julien-c!~julien-c@tru75-6-82-240-32-161.fbx.proxad.net',
                    'nick' => 'julien-c',
                    'user' => '~julien-c',
                    'host' => 'tru75-6-82-240-32-161.fbx.proxad.net',
                    'command' => 'PART',
                    'params' => [
                        'channels' => '#laravel',
                        'all' => '#laravel',
                    ],
                    'targets' => ['#laravel'],
                    'message' => ":julien-c!~julien-c@tru75-6-82-240-32-161.fbx.proxad.net PART #laravel\r\n",
                ],
            ],

            // Rizon allows nicks valid under RFC 2812, but not RFC 1459
            [
                "PRIVMSG ____ :test\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => '____',
                        'text' => 'test',
                        'all' => '____ :test',
                    ],
                    'targets' => ['____'],
                ],
            ],

            [
                "PRIVMSG |blah :test\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => '|blah',
                        'text' => 'test',
                        'all' => '|blah :test',
                    ],
                    'targets' => ['|blah'],
                ],
            ],

            [
                "PRIVMSG hello|there :test\r\n",
                [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'hello|there',
                        'text' => 'test',
                        'all' => 'hello|there :test',
                    ],
                    'targets' => ['hello|there'],
                ],
            ],

            // Hostname/ident patterns
            [
                ":nick!ident@123.host.com PRIVMSG target :message\r\n",
                [
                    'prefix' => ':nick!ident@123.host.com',
                    'nick' => 'nick',
                    'user' => 'ident',
                    'host' => '123.host.com',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'all' => 'target :message',
                        'receivers' => 'target',
                        'text' => 'message',
                    ],
                    'targets' => ['target'],
                ],
            ],

            [
                ":nick!ident- PRIVMSG target :message\r\n",
                [
                    'prefix' => ':nick!ident-',
                    'nick' => 'nick',
                    'user' => 'ident-',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'all' => 'target :message',
                        'receivers' => 'target',
                        'text' => 'message',
                    ],
                    'targets' => ['target'],
                ],
            ],

            [
                ":nick!ident@localhost PRIVMSG target :message\r\n",
                [
                    'prefix' => ':nick!ident@localhost',
                    'nick' => 'nick',
                    'user' => 'ident',
                    'host' => 'localhost',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'all' => 'target :message',
                        'receivers' => 'target',
                        'text' => 'message',
                    ],
                    'targets' => ['target'],
                ],
            ],

            // Check that the string '0' is not filtered out from a params list
            [
                "USER myident 0 * :Ronnie Reagan\r\n",
                [
                    'command' => 'USER',
                    'params' => [
                        'all' => 'myident 0 * :Ronnie Reagan',
                        'username' => 'myident',
                        'hostname' => '0',
                        'servername' => '*',
                        'realname' => 'Ronnie Reagan',
                    ],
                    'targets' => ['myident'],
                ],
            ],

            // Color codes in hostname. Possible for example on Rizon.
            [
                ":Float_!~pi@\x034Float\x030.\x0310Rizon\x030.\x034Rules\x03 JOIN :#/b/\r\n",
                [
                    'prefix' => ":Float_!~pi@\x034Float\x030.\x0310Rizon\x030.\x034Rules\x03",
                    'nick' => 'Float_',
                    'user' => '~pi',
                    'host' => "\x034Float\x030.\x0310Rizon\x030.\x034Rules\x03",
                    'command' => 'JOIN',
                    'params' => [
                        'all' => ':#/b/',
                        'channels' => '#/b/',
                    ],
                    'targets' => ['#/b/'],
                ],
            ],

            // Asterisk (*) in nickname. Used mainly by modules of IRC bouncers like ZNC.
            [
                ":*status!znc@znc.in PRIVMSG thebot :Error from Server [Closing Link: 127.0.0.1 (Killed (linear (youtube loop)))]\r\n",
                [
                    'prefix' => ":*status!znc@znc.in",
                    'nick' => '*status',
                    'user' => 'znc',
                    'host' => "znc.in",
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'thebot',
                        'text' => 'Error from Server [Closing Link: 127.0.0.1 (Killed (linear (youtube loop)))]',
                        'all' => 'thebot :Error from Server [Closing Link: 127.0.0.1 (Killed (linear (youtube loop)))]',
                    ],
                    'targets' => ['thebot'],
                ],
            ],

            // On Quakenet it seems that server can set usermode.
            [
                ":*.quakenet.org MODE #example +o mike1256\r\n",
                [
                    'prefix' => ":*.quakenet.org",
                    'servername' => '*.quakenet.org',
                    'command' => 'MODE',
                    'params' => [
                        'all' => '#example +o mike1256',
                        'mode' => '+o',
                        'channel' => '#example',
                        'user' => 'mike1256',
                        'params' => 'mike1256',
                    ],
                    'targets' => ['#example'],
                ],
            ],
        ];

        foreach ($data as $key => $value) {
            // Assume the string to parse contains the whole message for those
            // that don't explicitly specify that the two are different
            if (is_array($value[1]) && !isset($value[1]['message']) && !isset($value[1]['invalid'])) {
                $value[1]['message'] = $value[0];
            }

            // Add data sets for NOTICE equivalent to those for PRIVMSG
            if (strpos($value[0], 'PRIVMSG ') === 0) {
                $copy = $value;
                $copy[0] = substr_replace($copy[0], 'NOTICE', 0, 7);
                $copy[1]['message'] = substr_replace($copy[1]['message'], 'NOTICE', 0, 7);
                $copy[1]['command'] = 'NOTICE';
                $copy[1]['params']['nickname'] = $copy[1]['params']['receivers'];
                unset($copy[1]['params']['receivers']);
                $data[] = $copy;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Data provider for testParseAll() and testConsumeAll().
     *
     * @return array
     */
    public function dataProviderTestParseAll()
    {
        $message1 = [
            'string' => ":Angel PRIVMSG Wiz :Hello are you receiving this message ?",
            'parsed' => [
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'Wiz',
                        'text' => 'Hello are you receiving this message ?',
                        'all' => 'Wiz :Hello are you receiving this message ?',
                    ],
                    'targets' => ['Wiz'],
                ],
            ];

        $message2 = [
            'string' => "PRIVMSG Angel :yes I'm receiving it !receiving it !'u>(768u+1n) .br",
            'parsed' => [
                    'command' => 'PRIVMSG',
                    'params' => [
                        'receivers' => 'Angel',
                        'text' => 'yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                        'all' => 'Angel :yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                    ],
                    'targets' => ['Angel'],
                ],
            ];

        $data = [];

        // No messages
        $message = '';
        $data[] = [$message, []];

        // One incomplete message
        $message .= $message1['string'];
        $data[] = [$message, []];

        // One complete message
        $message .= "\r\n";
        $expected = $message1['parsed'];
        $expected['message'] = $message;
        unset($expected['tail']);
        $data[] = [$message, [$expected]];

        // One complete message, one incomplete message
        $message .= $message2['string'];
        $expected['tail'] = $message2['string'];
        $data[] = [$message, [$expected]];

        // Two complete messages
        $message .= "\r\n";
        $message1['parsed']['message'] = $message1['string'] . "\r\n";
        $message2['parsed']['message'] = $message1['parsed']['tail'] = $message2['string'] . "\r\n";
        $data[] = [$message, [$message1['parsed'], $message2['parsed']]];

        return $data;
    }
}
