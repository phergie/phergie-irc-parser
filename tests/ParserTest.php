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
        $data = array(

            // Empty message
            array(
                '',
                null
            ),

            // No CRLF
            array(
                'REHASH',
                null
            ),
            array(
                'NICK :Elazar',
                null,
            ),

            // Data past the first message should be stored as 'tail'
            array(
                "USER guest tolmoon tolsun :Ronnie Regan\r\nNICK :Wiz",
                array(
                    'command' => 'USER',
                    'params' => array(
                        'username' => 'guest',
                        'hostname' => 'tolmoon',
                        'servername' => 'tolsun',
                        'realname' => 'Ronnie Regan',
                        'all' => 'guest tolmoon tolsun :Ronnie Regan',
                    ),
                    'targets' => array('guest'),
                    'message' => "USER guest tolmoon tolsun :Ronnie Regan\r\n",
                    'tail' => 'NICK :Wiz',
                ),
            ),

            array(
                ":this:message:is:invalid\r\nNICK :Wiz",
                array(
                    'invalid' => ":this:message:is:invalid\r\n",
                    'tail' => 'NICK :Wiz',
                ),
            ),

            // PASS (RFC 1459 Section 4.1.1)
            array(
                "PASS :secretpasswordhere\r\n",
                array(
                    'command' => 'PASS',
                    'params' => array(
                        'password' => 'secretpasswordhere',
                        'all' => ':secretpasswordhere',
                    ),
                    'targets' => array('secretpasswordhere'),
                ),
            ),

            // NICK (RFC 1459 Section 4.1.2)
            array(
                "NICK :Wiz\r\n",
                array(
                    'command' => 'NICK',
                    'params' => array(
                        'nickname' => 'Wiz',
                        'all' => ':Wiz',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),
            
            array(
                "NICK :Wiz_\r\n",
                array(
                    'command' => 'NICK',
                    'params' => array(
                        'nickname' => 'Wiz_',
                        'all' => ':Wiz_',
                    ),
                    'targets' => array('Wiz_'),
                ),
            ),

            array(
                "NICK Wiz :1\r\n",
                array(
                    'command' => 'NICK',
                    'params' => array(
                        'nickname' => 'Wiz',
                        'hopcount' => '1',
                        'all' => 'Wiz :1',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            array(
                ":WiZ NICK :Kilroy\r\n",
                array(
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'NICK',
                    'params' => array(
                        'nickname' => 'Kilroy',
                        'all' => ':Kilroy',
                    ),
                    'targets' => array('Kilroy'),
                ),
            ),

            // USER (RFC 1459 Section 4.1.3)
            array(
                "USER guest tolmoon tolsun :Ronnie Regan\r\n",
                array(
                    'command' => 'USER',
                    'params' => array(
                        'username' => 'guest',
                        'hostname' => 'tolmoon',
                        'servername' => 'tolsun',
                        'realname' => 'Ronnie Regan',
                        'all' => 'guest tolmoon tolsun :Ronnie Regan',
                    ),
                    'targets' => array('guest'),
                ),
            ),

            array(
                ":testnick USER guest tolmoon tolsun :Ronnie Regan\r\n",
                array(
                    'prefix' => ':testnick',
                    'nick' => 'testnick',
                    'command' => 'USER',
                    'params' => array(
                        'hostname' => 'tolmoon',
                        'realname' => 'Ronnie Regan',
                        'servername' => 'tolsun',
                        'username' => 'guest',
                        'all' => 'guest tolmoon tolsun :Ronnie Regan',
                    ),
                    'targets' => array('guest'),
                ),
            ),

            // SERVER (RFC 1459 Section 4.1.4)
            array(
                "SERVER test.oulu.fi 1 :[tolsun.oulu.fi] Experimental server\r\n",
                array(
                    'command' => 'SERVER',
                    'params' => array(
                        'servername' => 'test.oulu.fi',
                        'hopcount' => '1',
                        'info' => '[tolsun.oulu.fi] Experimental server',
                        'all' => 'test.oulu.fi 1 :[tolsun.oulu.fi] Experimental server',
                    ),
                ),
            ),

            array(
                ":tolsun.oulu.fi SERVER csd.bu.edu 5 :BU Central Server\r\n",
                array(
                    'prefix' => ':tolsun.oulu.fi',
                    'servername' => 'tolsun.oulu.fi',
                    'command' => 'SERVER',
                    'params' => array(
                        'servername' => 'csd.bu.edu',
                        'hopcount' => '5',
                        'info' => 'BU Central Server',
                        'all' => 'csd.bu.edu 5 :BU Central Server',
                    ),
                ),
            ),

            // OPER (RFC 1459 Section 4.1.5)
            array(
                "OPER foo :bar\r\n",
                array(
                    'command' => 'OPER',
                    'params' => array(
                        'user' => 'foo',
                        'password' => 'bar',
                        'all' => 'foo :bar',
                    ),
                    'targets' => array('foo'),
                ),
            ),

            // QUIT (RFC 1459 Section 4.1.6)
            array(
                "QUIT\r\n",
                array(
                    'command' => 'QUIT',
                ),
            ),

            array(
                "QUIT :Gone to have lunch\r\n",
                array(
                    'command' => 'QUIT',
                    'params' => array(
                        'message' => 'Gone to have lunch',
                        'all' => ':Gone to have lunch',
                    ),
                ),
            ),

            // SQUIT (RFC 1459 Section 4.1.7)
            array(
                "SQUIT tolsun.oulu.fi :Bad Link ?\r\n",
                array(
                    'command' => 'SQUIT',
                    'params' => array(
                        'server' => 'tolsun.oulu.fi',
                        'comment' => 'Bad Link ?',
                        'all' => 'tolsun.oulu.fi :Bad Link ?',
                    ),
                ),
            ),

            array(
                ":Trillian SQUIT cm22.eng.umd.edu :Server out of control\r\n",
                array(
                    'prefix' => ':Trillian',
                    'nick' => 'Trillian',
                    'command' => 'SQUIT',
                    'params' => array(
                        'server' => 'cm22.eng.umd.edu',
                        'comment' => 'Server out of control',
                        'all' => 'cm22.eng.umd.edu :Server out of control',
                    ),
                ),
            ),

            // JOIN (RFC 1459 Section 4.2.1)
            array(
                "JOIN :#foobar\r\n",
                array(
                    'command' => 'JOIN',
                    'params' => array(
                        'channels' => '#foobar',
                        'all' => ':#foobar',
                    ),
                    'targets' => array('#foobar'),
                ),
            ),

            array(
                "JOIN &foo :fubar\r\n",
                array(
                    'command' => 'JOIN',
                    'params' => array(
                        'channels' => '&foo',
                        'keys' => 'fubar',
                        'all' => '&foo :fubar',
                    ),
                    'targets' => array('&foo'),
                ),
            ),

            array(
                "JOIN #foo,&bar :fubar\r\n",
                array(
                    'command' => 'JOIN',
                    'params' => array(
                        'channels' => '#foo,&bar',
                        'keys' => 'fubar',
                        'all' => '#foo,&bar :fubar',
                    ),
                    'targets' => array('#foo', '&bar'),
                ),
            ),

            array(
                "JOIN #foo,#bar :fubar,foobar\r\n",
                array(
                    'command' => 'JOIN',
                    'params' => array(
                        'channels' => '#foo,#bar',
                        'keys' => 'fubar,foobar',
                        'all' => '#foo,#bar :fubar,foobar',
                    ),
                    'targets' => array('#foo', '#bar'),
                ),
            ),

            array(
                "JOIN :#foo,#bar\r\n",
                array(
                    'command' => 'JOIN',
                    'params' => array(
                        'channels' => '#foo,#bar',
                        'all' => ':#foo,#bar',
                    ),
                    'targets' => array('#foo', '#bar'),
                ),
            ),

            array(
                ":WiZ JOIN :#Twilight_zone\r\n",
                array(
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'JOIN',
                    'params' => array(
                        'channels' => '#Twilight_zone',
                        'all' => ':#Twilight_zone',
                    ),
                    'targets' => array('#Twilight_zone'),
                ),
            ),

            // PART (RFC 2182 Section 3.2.2)
            array(
                "PART :#twilight_zone\r\n",
                array(
                    'command' => 'PART',
                    'params' => array(
                        'channels' => '#twilight_zone',
                        'all' => ':#twilight_zone',
                    ),
                    'targets' => array('#twilight_zone'),
                ),
            ),

            array(
                "PART :#oz-ops,&group5\r\n",
                array(
                    'command' => 'PART',
                    'params' => array(
                        'channels' => '#oz-ops,&group5',
                        'all' => ':#oz-ops,&group5',
                    ),
                    'targets' => array('#oz-ops', '&group5'),
                ),
            ),

            array(
                ":WiZ!jto@tolsun.oulu.fi PART #playzone :I lost\r\n",
                array(
                    'prefix' => ':WiZ!jto@tolsun.oulu.fi',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'tolsun.oulu.fi',
                    'command' => 'PART',
                    'params' => array(
                        'channels' => '#playzone',
                        'message' => 'I lost',
                        'all' => '#playzone :I lost',
                    ),
                    'targets' => array('#playzone'),
                ),
            ),

            // Some servers use weird cloaked hostnames, although it's not RFC conform
            array(
                ":WiZ!jto@DCE7E23D:1D6D03E4:2248D1C4:IP PART #playzone :I lost\r\n",
                array(
                    'prefix' => ':WiZ!jto@DCE7E23D:1D6D03E4:2248D1C4:IP',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'DCE7E23D:1D6D03E4:2248D1C4:IP',
                    'command' => 'PART',
                    'params' => array(
                            'channels' => '#playzone',
                            'message' => 'I lost',
                            'all' => '#playzone :I lost',
                    ),
                    'targets' => array('#playzone'),
                ),
            ),
            array(
                ":WiZ!jto@facebook/hhvm/sgolemon PART #playzone :I lost\r\n",
                array(
                    'prefix' => ':WiZ!jto@facebook/hhvm/sgolemon',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'facebook/hhvm/sgolemon',
                    'command' => 'PART',
                    'params' => array(
                            'channels' => '#playzone',
                            'message' => 'I lost',
                            'all' => '#playzone :I lost',
                    ),
                    'targets' => array('#playzone'),
                ),
            ),
            array(
                ":WiZ!jto@gateway/web/irccloud.com/x-yjyvvvvrtuiwaqco PART #playzone :I lost\r\n",
                array(
                    'prefix' => ':WiZ!jto@gateway/web/irccloud.com/x-yjyvvvvrtuiwaqco',
                    'nick' => 'WiZ',
                    'user' => 'jto',
                    'host' => 'gateway/web/irccloud.com/x-yjyvvvvrtuiwaqco',
                    'command' => 'PART',
                    'params' => array(
                            'channels' => '#playzone',
                            'message' => 'I lost',
                            'all' => '#playzone :I lost',
                    ),
                    'targets' => array('#playzone'),
                ),
            ),

            // MODE (RFC 1459 Section 4.2.3)
            array(
                "MODE #Finnish :+im\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#Finnish',
                        'mode' => '+im',
                        'all' => '#Finnish :+im',
                    ),
                    'targets' => array('#Finnish'),
                ),
            ),

            array(
                "MODE #Finnish +o :Kilroy\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#Finnish',
                        'mode' => '+o',
                        'params' => 'Kilroy',
                        'user' => 'Kilroy',
                        'all' => '#Finnish +o :Kilroy',
                    ),
                    'targets' => array('#Finnish'),
                ),
            ),

            array(
                "MODE #Finnish +v :Wiz\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#Finnish',
                        'mode' => '+v',
                        'params' => 'Wiz',
                        'user' => 'Wiz',
                        'all' => '#Finnish +v :Wiz',
                    ),
                    'targets' => array('#Finnish'),
                ),
            ),

            array(
                "MODE #Finnish +ov :Kilroy Wiz\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#Finnish',
                        'mode' => '+ov',
                        'params' => 'Kilroy Wiz',
                        'all' => '#Finnish +ov :Kilroy Wiz',
                    ),
                    'targets' => array('#Finnish'),
                ),
            ),

            array(
                "MODE #Finnish +mvv-v :Kilroy Wiz Angel\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#Finnish',
                        'mode' => '+mvv-v',
                        'params' => 'Kilroy Wiz Angel',
                        'all' => '#Finnish +mvv-v :Kilroy Wiz Angel',
                    ),
                    'targets' => array('#Finnish'),
                ),
            ),

            array(
                "MODE #Fins :-s\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#Fins',
                        'mode' => '-s',
                        'all' => '#Fins :-s',
                    ),
                    'targets' => array('#Fins'),
                ),
            ),

            array(
                "MODE #42 +k :oulu\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#42',
                        'mode' => '+k',
                        'params' => 'oulu',
                        'key' => 'oulu',
                        'all' => '#42 +k :oulu',
                    ),
                    'targets' => array('#42'),
                ),
            ),

            array(
                "MODE #42 +ks :oulu\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#42',
                        'mode' => '+ks',
                        'params' => 'oulu',
                        'all' => '#42 +ks :oulu',
                    ),
                    'targets' => array('#42'),
                ),
            ),

            array(
                "MODE #eu-opers +l :10\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#eu-opers',
                        'mode' => '+l',
                        'params' => '10',
                        'limit' => '10',
                        'all' => '#eu-opers +l :10',
                    ),
                    'targets' => array('#eu-opers'),
                ),
            ),

            array(
                "MODE #eu-opers +lL :10 #eu-opers-overflow\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#eu-opers',
                        'mode' => '+lL',
                        'params' => '10 #eu-opers-overflow',
                        'all' => '#eu-opers +lL :10 #eu-opers-overflow',
                    ),
                    'targets' => array('#eu-opers'),
                ),
            ),

            array(
                "MODE &oulu :+b\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '&oulu',
                        'mode' => '+b',
                        'all' => '&oulu :+b',
                    ),
                    'targets' => array('&oulu'),
                ),
            ),

            array(
                "MODE &oulu +b :*!*@*\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '&oulu',
                        'mode' => '+b',
                        'params' => '*!*@*',
                        'banmask' => '*!*@*',
                        'all' => '&oulu +b :*!*@*',
                    ),
                    'targets' => array('&oulu'),
                ),
            ),

            array(
                "MODE &oulu +b :*!*@*.edu\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '&oulu',
                        'mode' => '+b',
                        'params' => '*!*@*.edu',
                        'banmask' => '*!*@*.edu',
                        'all' => '&oulu +b :*!*@*.edu',
                    ),
                    'targets' => array('&oulu'),
                ),
            ),

            array(
                "MODE &oulu +b-b :*!*@*.edu *!*@*.ac.uk\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '&oulu',
                        'mode' => '+b-b',
                        'params' => '*!*@*.edu *!*@*.ac.uk',
                        'all' => '&oulu +b-b :*!*@*.edu *!*@*.ac.uk',
                    ),
                    'targets' => array('&oulu'),
                ),
            ),

            array(
                "MODE Wiz :-w\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'user' => 'Wiz',
                        'mode' => '-w',
                        'all' => 'Wiz :-w',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            array(
                ":Angel MODE Angel :+i\r\n",
                array(
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'MODE',
                    'params' => array(
                        'user' => 'Angel',
                        'mode' => '+i',
                        'all' => 'Angel :+i',
                    ),
                    'targets' => array('Angel'),
                ),
            ),

            array(
                "MODE Wiz :-o\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'user' => 'Wiz',
                        'mode' => '-o',
                        'all' => 'Wiz :-o',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            array(
                "MODE Kilroy +s :+CcQq\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'user' => 'Kilroy',
                        'mode' => '+s',
                        'params' => '+CcQq',
                        'all' => 'Kilroy +s :+CcQq',
                    ),
                    'targets' => array('Kilroy'),
                ),
            ),

            array(
                "MODE Angel +ws :+CcQq\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'user' => 'Angel',
                        'mode' => '+ws',
                        'params' => '+CcQq',
                        'all' => 'Angel +ws :+CcQq',
                    ),
                    'targets' => array('Angel'),
                ),
            ),

            array(
                "MODE :#channel\r\n",
                array(
                    'command' => 'MODE',
                    'params' => array(
                        'channel' => '#channel',
                        'all' => ':#channel',
                    ),
                    'targets' => array('#channel'),
                ),
            ),

            // TOPIC (RFC 1459 Section 4.2.4)
            array(
                ":Wiz TOPIC #test :New topic\r\n",
                array(
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'TOPIC',
                    'params' => array(
                        'channel' => '#test',
                        'topic' => 'New topic',
                        'all' => '#test :New topic',
                    ),
                    'targets' => array('#test'),
                ),
            ),

            array(
                "TOPIC #test :another topic\r\n",
                array(
                    'command' => 'TOPIC',
                    'params' => array(
                        'channel' => '#test',
                        'topic' => 'another topic',
                        'all' => '#test :another topic',
                    ),
                    'targets' => array('#test'),
                ),
            ),

            array(
                "TOPIC :#test\r\n",
                array(
                    'command' => 'TOPIC',
                    'params' => array(
                        'channel' => '#test',
                        'all' => ':#test',
                    ),
                    'targets' => array('#test'),
                ),
            ),

            // NAMES (RFC 1459 Section 4.2.5)
            array(
                "NAMES :#twilight_zone,#42\r\n",
                array(
                    'command' => 'NAMES',
                    'params' => array(
                        'channels' => '#twilight_zone,#42',
                        'all' => ':#twilight_zone,#42',
                    ),
                    'targets' => array('#twilight_zone', '#42'),
                ),
            ),

            array(
                "NAMES\r\n",
                array(
                    'command' => 'NAMES',
                ),
            ),

            // LIST (RFC 1459 Section 4.2.6)
            array(
                "LIST\r\n",
                array(
                    'command' => 'LIST',
                ),
            ),

            array(
                "LIST :#twilight_zone,#42\r\n",
                array(
                    'command' => 'LIST',
                    'params' => array(
                        'channels' => '#twilight_zone,#42',
                        'all' => ':#twilight_zone,#42',
                    ),
                    'targets' => array('#twilight_zone', '#42'),
                ),
            ),

            // INVITE (RFC 1459 Section 4.2.7)
            array(
                ":Angel INVITE Wiz :#Dust\r\n",
                array(
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'INVITE',
                    'params' => array(
                        'nickname' => 'Wiz',
                        'channel' => '#Dust',
                        'all' => 'Wiz :#Dust',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            array(
                "INVITE Wiz :#Twilight_Zone\r\n",
                array(
                    'command' => 'INVITE',
                    'params' => array(
                        'nickname' => 'Wiz',
                        'channel' => '#Twilight_Zone',
                        'all' => 'Wiz :#Twilight_Zone',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            // KICK (RFC 1459 Section 4.2.8)
            array(
                "KICK &Melbourne :Matthew\r\n",
                array(
                    'command' => 'KICK',
                    'params' => array(
                        'channel' => '&Melbourne',
                        'user' => 'Matthew',
                        'all' => '&Melbourne :Matthew',
                    ),
                    'targets' => array('&Melbourne'),
                ),
            ),

            array(
                "KICK #Finnish John :Speaking English\r\n",
                array(
                    'command' => 'KICK',
                    'params' => array(
                        'channel' => '#Finnish',
                        'user' => 'John',
                        'comment' => 'Speaking English',
                        'all' => '#Finnish John :Speaking English',
                    ),
                    'targets' => array('#Finnish'),
                ),
            ),

            array(
                ":WiZ KICK #Finnish :John\r\n",
                array(
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'KICK',
                    'params' => array(
                        'channel' => '#Finnish',
                        'user' => 'John',
                        'all' => '#Finnish :John',
                    ),
                    'targets' => array('#Finnish'),
                ),
            ),

            // VERSION (RFC 1459 Section 4.3.1)
            array(
                ":Wiz VERSION :*.se\r\n",
                array(
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'VERSION',
                    'params' => array(
                        'server' => '*.se',
                        'all' => ':*.se',
                    ),
                ),
            ),

            array(
                "VERSION :tolsun.oulu.fi\r\n",
                array(
                    'command' => 'VERSION',
                    'params' => array(
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ),
                ),
            ),

            // STATS (RFC 1459 Section 4.3.2)
            array(
                "STATS :m\r\n",
                array(
                    'command' => 'STATS',
                    'params' => array(
                        'query' => 'm',
                        'all' => ':m',
                    ),
                    'targets' => array('m'),
                ),
            ),

            array(
                ":Wiz STATS c :eff.org\r\n",
                array(
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'STATS',
                    'params' => array(
                        'query' => 'c',
                        'server' => 'eff.org',
                        'all' => 'c :eff.org',
                    ),
                    'targets' => array('c'),
                ),
            ),

            // LINKS (RFC 1459 Section 4.3.3)
            array(
                "LINKS :*.au\r\n",
                array(
                    'command' => 'LINKS',
                    'params' => array(
                        'servermask' => '*.au',
                        'all' => ':*.au',
                    ),
                ),
            ),

            array(
                ":WiZ LINKS *.bu.edu :*.edu\r\n",
                array(
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'LINKS',
                    'params' => array(
                        'remoteserver' => '*.bu.edu',
                        'servermask' => '*.edu',
                        'all' => '*.bu.edu :*.edu',
                    ),
                ),
            ),

            // TIME (RFC 1459 Section 4.3.4)
            array(
                "TIME :tolsun.oulu.fi\r\n",
                array(
                    'command' => 'TIME',
                    'params' => array(
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ),
                ),
            ),

            array(
                ":Angel TIME :*.au\r\n",
                array(
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'TIME',
                    'params' => array(
                        'server' => '*.au',
                        'all' => ':*.au',
                    ),
                ),
            ),

            // CONNECT (RFC 1459 Section 4.3.5)
            array(
                "CONNECT :tolsun.oulu.fi\r\n",
                array(
                    'command' => 'CONNECT',
                    'params' => array(
                        'targetserver' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ),
                ),
            ),

            array(
                ":WiZ CONNECT eff.org 6667 :csd.bu.edu\r\n",
                array(
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'CONNECT',
                    'params' => array(
                        'targetserver' => 'eff.org',
                        'port' => '6667',
                        'remoteserver' => 'csd.bu.edu',
                        'all' => 'eff.org 6667 :csd.bu.edu',
                    ),
                ),
            ),

            // TRACE (RFC 1459 Section 4.3.6)
            array(
                "TRACE :*.oulu.fi\r\n",
                array(
                    'command' => 'TRACE',
                    'params' => array(
                        'server' => '*.oulu.fi',
                        'all' => ':*.oulu.fi',
                    ),
                ),
            ),

            array(
                ":WiZ TRACE :AngelDust\r\n",
                array(
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'TRACE',
                    'params' => array(
                        'server' => 'AngelDust',
                        'all' => ':AngelDust',
                    ),
                    'targets' => array('AngelDust'),
                ),
            ),

            // ADMIN (RFC 1459 Section 4.3.7)
            array(
                "ADMIN :tolsun.oulu.fi\r\n",
                array(
                    'command' => 'ADMIN',
                    'params' => array(
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ),
                ),
            ),

            array(
                ":WiZ ADMIN :*.edu\r\n",
                array(
                    'prefix' => ':WiZ',
                    'nick' => 'WiZ',
                    'command' => 'ADMIN',
                    'params' => array(
                        'server' => '*.edu',
                        'all' => ':*.edu',
                    ),
                ),
            ),

            // INFO (RFC 1459 Section 4.3.8)
            array(
                "INFO :csd.bu.edu\r\n",
                array(
                    'command' => 'INFO',
                    'params' => array(
                        'server' => 'csd.bu.edu',
                        'all' => ':csd.bu.edu',
                    ),
                ),
            ),

            array(
                ":Avalon INFO :*.fi\r\n",
                array(
                    'prefix' => ':Avalon',
                    'nick' => 'Avalon',
                    'command' => 'INFO',
                    'params' => array(
                        'server' => '*.fi',
                        'all' => ':*.fi',
                    ),
                ),
            ),

            array(
                "INFO :Angel\r\n",
                array(
                    'command' => 'INFO',
                    'params' => array(
                        'server' => 'Angel',
                        'all' => ':Angel',
                    ),
                    'targets' => array('Angel'),
                ),
            ),

            // PRIVMSG (RFC 1459 Section 4.4.1)
            array(
                ":Angel PRIVMSG Wiz :Hello are you receiving this message ?\r\n",
                array(
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'Wiz',
                        'text' => 'Hello are you receiving this message ?',
                        'all' => 'Wiz :Hello are you receiving this message ?',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            array(
                "PRIVMSG Angel :yes I'm receiving it !receiving it !'u>(768u+1n) .br\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'Angel',
                        'text' => 'yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                        'all' => 'Angel :yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                    ),
                    'targets' => array('Angel'),
                ),
            ),

            array(
                "PRIVMSG jto@tolsun.oulu.fi :Hello !\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'jto@tolsun.oulu.fi',
                        'text' => 'Hello !',
                        'all' => 'jto@tolsun.oulu.fi :Hello !',
                    ),
                    'targets' => array('jto@tolsun.oulu.fi'),
                ),
            ),

            array(
                "PRIVMSG $*.fi :Server tolsun.oulu.fi rebooting.\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => '$*.fi',
                        'text' => 'Server tolsun.oulu.fi rebooting.',
                        'all' => '$*.fi :Server tolsun.oulu.fi rebooting.',
                    ),
                    'targets' => array('$*.fi'),
                ),
            ),

            array(
                "PRIVMSG #*.edu :NSFNet is undergoing work, expect interruptions\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => '#*.edu',
                        'text' => 'NSFNet is undergoing work, expect interruptions',
                        'all' => '#*.edu :NSFNet is undergoing work, expect interruptions',
                    ),
                    'targets' => array('#*.edu'),
                ),
            ),

            // NOTE: Because of syntactic equivalence, data sets for NOTICE
            // (RFC 1459 Section 4.4.2) equivalent to those for PRIVMSG are
            // derived later in this method rather than being duplicated here

            // WHO (RFC 1459 Section 4.5.1)
            array(
                "WHO :*.fi\r\n",
                array(
                    'command' => 'WHO',
                    'params' => array(
                        'name' => '*.fi',
                        'all' => ':*.fi',
                    ),
                ),
            ),

            array(
                "WHO jto* :o\r\n",
                array(
                    'command' => 'WHO',
                    'params' => array(
                        'name' => 'jto*',
                        'o' => 'o',
                        'all' => 'jto* :o',
                    ),
                ),
            ),

            // WHOIS (RFC 1459 Section 4.5.2)
            array(
                "WHOIS :wiz\r\n",
                array(
                    'command' => 'WHOIS',
                    'params' => array(
                        'nickmasks' => 'wiz',
                        'all' => ':wiz',
                    ),
                ),
            ),

            array(
                "WHOIS eff.org :Trillian\r\n",
                array(
                    'command' => 'WHOIS',
                    'params' => array(
                        'server' => 'eff.org',
                        'nickmasks' => 'Trillian',
                        'all' => 'eff.org :Trillian',
                    ),
                ),
            ),

            // WHOWAS (RFC 1459 Section 4.5.3)
            array(
                "WHOWAS :Wiz\r\n",
                array(
                    'command' => 'WHOWAS',
                    'params' => array(
                        'nickname' => 'Wiz',
                        'all' => ':Wiz',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            array(
                "WHOWAS Mermaid :9\r\n",
                array(
                    'command' => 'WHOWAS',
                    'params' => array(
                        'nickname' => 'Mermaid',
                        'count' => '9',
                        'all' => 'Mermaid :9',
                    ),
                    'targets' => array('Mermaid'),
                ),
            ),

            array(
                "WHOWAS Trillian 1 :*.edu\r\n",
                array(
                    'command' => 'WHOWAS',
                    'params' => array(
                        'nickname' => 'Trillian',
                        'count' => '1',
                        'server' => '*.edu',
                        'all' => 'Trillian 1 :*.edu',
                    ),
                    'targets' => array('Trillian'),
                ),
            ),

            // KILL (RFC 1459 Section 4.6.1)
            array(
                "KILL David :(csd.bu.edu <- tolsun.oulu.fi)\r\n",
                array(
                    'command' => 'KILL',
                    'params' => array(
                        'nickname' => 'David',
                        'comment' => '(csd.bu.edu <- tolsun.oulu.fi)',
                        'all' => 'David :(csd.bu.edu <- tolsun.oulu.fi)',
                    ),
                    'targets' => array('David'),
                ),
            ),

            // PING (RFC 1459 Section 4.6.2)
            array(
                "PING :tolsun.oulu.fi\r\n",
                array(
                    'command' => 'PING',
                    'params' => array(
                        'server1' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ),
                ),
            ),

            array(
                "PING :WiZ\r\n",
                array(
                    'command' => 'PING',
                    'params' => array(
                        'server1' => 'WiZ',
                        'all' => ':WiZ',
                    ),
                    'targets' => array('WiZ'),
                ),
            ),

            // PONG (RFC 1459 Section 4.6.3)
            array(
                "PONG csd.bu.edu :tolsun.oulu.fi\r\n",
                array(
                    'command' => 'PONG',
                    'params' => array(
                        'daemon' => 'csd.bu.edu',
                        'daemon2' => 'tolsun.oulu.fi',
                        'all' => 'csd.bu.edu :tolsun.oulu.fi',
                    ),
                ),
            ),

            // ERROR (RFC 1459 Section 4.6.4)
            array(
                "ERROR :Server *.fi already exists\r\n",
                array(
                    'command' => 'ERROR',
                    'params' => array(
                        'message' => 'Server *.fi already exists',
                        'all' => ':Server *.fi already exists',
                    ),
                ),
            ),

            array(
                "NOTICE WiZ :ERROR from csd.bu.edu -- Server *.fi already exists\r\n",
                array(
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'WiZ',
                        'text' => 'ERROR from csd.bu.edu -- Server *.fi already exists',
                        'all' => 'WiZ :ERROR from csd.bu.edu -- Server *.fi already exists',
                    ),
                    'targets' => array('WiZ'),
                ),
            ),

            // AWAY (RFC 1459 Section 5.1)
            array(
                "AWAY :Gone to lunch.\r\n",
                array(
                    'command' => 'AWAY',
                    'params' => array(
                        'message' => 'Gone to lunch.',
                        'all' => ':Gone to lunch.',
                    ),
                ),
            ),

            array(
                ":Wiz AWAY\r\n",
                array(
                    'prefix' => ':Wiz',
                    'nick' => 'Wiz',
                    'command' => 'AWAY',
                ),
            ),

            // REHASH (RFC 1459 Section 5.2)
            array(
                "REHASH\r\n",
                array(
                    'command' => 'REHASH',
                ),
            ),

            // RESTART (RFC 1459 Section 5.3)
            array(
                "RESTART\r\n",
                array(
                    'command' => 'RESTART',
                ),
            ),

            // SUMMON (RFC 1459 Section 5.4)
            array(
                "SUMMON :jto\r\n",
                array(
                    'command' => 'SUMMON',
                    'params' => array(
                        'user' => 'jto',
                        'all' => ':jto',
                    ),
                    'targets' => array('jto'),
                ),
            ),

            array(
                "SUMMON jto :tolsun.oulu.fi\r\n",
                array(
                    'command' => 'SUMMON',
                    'params' => array(
                        'user' => 'jto',
                        'server' => 'tolsun.oulu.fi',
                        'all' => 'jto :tolsun.oulu.fi',
                    ),
                    'targets' => array('jto'),
                ),
            ),

            // USERS (RFC 1459 Section 5.5)
            array(
                "USERS :eff.org\r\n",
                array(
                    'command' => 'USERS',
                    'params' => array(
                        'server' => 'eff.org',
                        'all' => ':eff.org',
                    ),
                ),
            ),

            array(
                ":John USERS :tolsun.oulu.fi\r\n",
                array(
                    'prefix' => ':John',
                    'nick' => 'John',
                    'command' => 'USERS',
                    'params' => array(
                        'server' => 'tolsun.oulu.fi',
                        'all' => ':tolsun.oulu.fi',
                    ),
                ),
            ),

            // WALLOPS (RFC 1459 Section 5.6)
            array(
                ":csd.bu.edu WALLOPS :Connect '*.uiuc.edu 6667' from Joshua\r\n",
                array(
                    'prefix' => ':csd.bu.edu',
                    'servername' => 'csd.bu.edu',
                    'command' => 'WALLOPS',
                    'params' => array(
                        'text' => 'Connect \'*.uiuc.edu 6667\' from Joshua',
                        'all' => ':Connect \'*.uiuc.edu 6667\' from Joshua',
                    ),
                ),
            ),

            // USERHOST (RFC 1459 Section 5.7)
            array(
                "USERHOST Wiz Michael Marty :p\r\n",
                array(
                    'command' => 'USERHOST',
                    'params' => array(
                        'nickname1' => 'Wiz',
                        'nickname2' => 'Michael',
                        'nickname3' => 'Marty',
                        'nickname4' => 'p',
                        'all' => 'Wiz Michael Marty :p',
                    ),
                    'targets' => array('Wiz'),
                ),
            ),

            // ISON (RFC 1459 Section 5.8)
            array(
                "ISON :phone trillian WiZ jarlek Avalon Angel Monstah\r\n",
                array(
                    'command' => 'ISON',
                    'params' => array(
                        'nicknames' => 'phone trillian WiZ jarlek Avalon Angel Monstah',
                        'all' => ':phone trillian WiZ jarlek Avalon Angel Monstah',
                    ),
                ),
            ),

            // PROTOCTL
            array(
                "PROTOCTL NAMESX\r\n",
                array(
                    'command' => 'PROTOCTL',
                    'params' => array(
                        'proto' => 'NAMESX',
                        'all' => 'NAMESX',
                    ),
                ),
            ),

            // Error replies (RFC 1459 Section 6.1)
            array(
                "401\r\n",
                array(
                    'command' => '401',
                    'code' => 'ERR_NOSUCHNICK',
                ),
            ),
            array(
                "402\r\n",
                array(
                    'command' => '402',
                    'code' => 'ERR_NOSUCHSERVER',
                ),
            ),
            array(
                "403\r\n",
                array(
                    'command' => '403',
                    'code' => 'ERR_NOSUCHCHANNEL',
                ),
            ),
            array(
                "404\r\n",
                array(
                    'command' => '404',
                    'code' => 'ERR_CANNOTSENDTOCHAN',
                ),
            ),
            array(
                "405\r\n",
                array(
                    'command' => '405',
                    'code' => 'ERR_TOOMANYCHANNELS',
                ),
            ),
            array(
                "406\r\n",
                array(
                    'command' => '406',
                    'code' => 'ERR_WASNOSUCHNICK',
                ),
            ),
            array(
                "407\r\n",
                array(
                    'command' => '407',
                    'code' => 'ERR_TOOMANYTARGETS',
                ),
            ),
            array(
                "409\r\n",
                array(
                    'command' => '409',
                    'code' => 'ERR_NOORIGIN',
                ),
            ),
            array(
                "411\r\n",
                array(
                    'command' => '411',
                    'code' => 'ERR_NORECIPIENT',
                ),
            ),
            array(
                "412\r\n",
                array(
                    'command' => '412',
                    'code' => 'ERR_NOTEXTTOSEND',
                ),
            ),
            array(
                "413\r\n",
                array(
                    'command' => '413',
                    'code' => 'ERR_NOTOPLEVEL',
                ),
            ),
            array(
                "414\r\n",
                array(
                    'command' => '414',
                    'code' => 'ERR_WILDTOPLEVEL',
                ),
            ),
            array(
                "421\r\n",
                array(
                    'command' => '421',
                    'code' => 'ERR_UNKNOWNCOMMAND',
                ),
            ),
            array(
                "422\r\n",
                array(
                    'command' => '422',
                    'code' => 'ERR_NOMOTD',
                ),
            ),
            array(
                "423\r\n",
                array(
                    'command' => '423',
                    'code' => 'ERR_NOADMININFO',
                ),
            ),
            array(
                "424\r\n",
                array(
                    'command' => '424',
                    'code' => 'ERR_FILEERROR',
                ),
            ),
            array(
                "431\r\n",
                array(
                    'command' => '431',
                    'code' => 'ERR_NONICKNAMEGIVEN',
                ),
            ),
            array(
                "432\r\n",
                array(
                    'command' => '432',
                    'code' => 'ERR_ERRONEUSNICKNAME',
                ),
            ),
            array(
                "433\r\n",
                array(
                    'command' => '433',
                    'code' => 'ERR_NICKNAMEINUSE',
                ),
            ),
            array(
                "436\r\n",
                array(
                    'command' => '436',
                    'code' => 'ERR_NICKCOLLISION',
                ),
            ),
            array(
                "441\r\n",
                array(
                    'command' => '441',
                    'code' => 'ERR_USERNOTINCHANNEL',
                ),
            ),
            array(
                "442\r\n",
                array(
                    'command' => '442',
                    'code' => 'ERR_NOTONCHANNEL',
                ),
            ),
            array(
                "443\r\n",
                array(
                    'command' => '443',
                    'code' => 'ERR_USERONCHANNEL',
                ),
            ),
            array(
                "444\r\n",
                array(
                    'command' => '444',
                    'code' => 'ERR_NOLOGIN',
                ),
            ),
            array(
                "445\r\n",
                array(
                    'command' => '445',
                    'code' => 'ERR_SUMMONDISABLED',
                ),
            ),
            array(
                "446\r\n",
                array(
                    'command' => '446',
                    'code' => 'ERR_USERSDISABLED',
                ),
            ),
            array(
                "451\r\n",
                array(
                    'command' => '451',
                    'code' => 'ERR_NOTREGISTERED',
                ),
            ),
            array(
                "461\r\n",
                array(
                    'command' => '461',
                    'code' => 'ERR_NEEDMOREPARAMS',
                ),
            ),
            array(
                "462\r\n",
                array(
                    'command' => '462',
                    'code' => 'ERR_ALREADYREGISTRED',
                ),
            ),
            array(
                "463\r\n",
                array(
                    'command' => '463',
                    'code' => 'ERR_NOPERMFORHOST',
                ),
            ),
            array(
                "464\r\n",
                array(
                    'command' => '464',
                    'code' => 'ERR_PASSWDMISMATCH',
                ),
            ),
            array(
                "465\r\n",
                array(
                    'command' => '465',
                    'code' => 'ERR_YOUREBANNEDCREEP',
                ),
            ),
            array(
                "467\r\n",
                array(
                    'command' => '467',
                    'code' => 'ERR_KEYSET',
                ),
            ),
            array(
                "471\r\n",
                array(
                    'command' => '471',
                    'code' => 'ERR_CHANNELISFULL',
                ),
            ),
            array(
                "472\r\n",
                array(
                    'command' => '472',
                    'code' => 'ERR_UNKNOWNMODE',
                ),
            ),
            array(
                "473\r\n",
                array(
                    'command' => '473',
                    'code' => 'ERR_INVITEONLYCHAN',
                ),
            ),
            array(
                "474\r\n",
                array(
                    'command' => '474',
                    'code' => 'ERR_BANNEDFROMCHAN',
                ),
            ),
            array(
                "475\r\n",
                array(
                    'command' => '475',
                    'code' => 'ERR_BADCHANNELKEY',
                ),
            ),
            array(
                "481\r\n",
                array(
                    'command' => '481',
                    'code' => 'ERR_NOPRIVILEGES',
                ),
            ),
            array(
                "482\r\n",
                array(
                    'command' => '482',
                    'code' => 'ERR_CHANOPRIVSNEEDED',
                ),
            ),
            array(
                "483\r\n",
                array(
                    'command' => '483',
                    'code' => 'ERR_CANTKILLSERVER',
                ),
            ),
            array(
                "491\r\n",
                array(
                    'command' => '491',
                    'code' => 'ERR_NOOPERHOST',
                ),
            ),
            array(
                "501\r\n",
                array(
                    'command' => '501',
                    'code' => 'ERR_UMODEUNKNOWNFLAG',
                ),
            ),
            array(
                "502\r\n",
                array(
                    'command' => '502',
                    'code' => 'ERR_USERSDONTMATCH',
                ),
            ),

            // Command responses (RFC 1459 Section 6.2)
            array(
                "300\r\n",
                array(
                    'command' => '300',
                    'code' => 'RPL_NONE',
                ),
            ),
            array(
                "302\r\n",
                array(
                    'command' => '302',
                    'code' => 'RPL_USERHOST',
                ),
            ),
            array(
                "303\r\n",
                array(
                    'command' => '303',
                    'code' => 'RPL_ISON',
                ),
            ),
            array(
                "301\r\n",
                array(
                    'command' => '301',
                    'code' => 'RPL_AWAY',
                ),
            ),
            array(
                "305\r\n",
                array(
                    'command' => '305',
                    'code' => 'RPL_UNAWAY',
                ),
            ),
            array(
                "306\r\n",
                array(
                    'command' => '306',
                    'code' => 'RPL_NOWAWAY',
                ),
            ),
            array(
                "311\r\n",
                array(
                    'command' => '311',
                    'code' => 'RPL_WHOISUSER',
                ),
            ),
            array(
                "312\r\n",
                array(
                    'command' => '312',
                    'code' => 'RPL_WHOISSERVER',
                ),
            ),
            array(
                "313\r\n",
                array(
                    'command' => '313',
                    'code' => 'RPL_WHOISOPERATOR',
                ),
            ),
            array(
                "317\r\n",
                array(
                    'command' => '317',
                    'code' => 'RPL_WHOISIDLE',
                ),
            ),
            array(
                "318\r\n",
                array(
                    'command' => '318',
                    'code' => 'RPL_ENDOFWHOIS',
                ),
            ),
            array(
                "319\r\n",
                array(
                    'command' => '319',
                    'code' => 'RPL_WHOISCHANNELS',
                ),
            ),
            array(
                "314\r\n",
                array(
                    'command' => '314',
                    'code' => 'RPL_WHOWASUSER',
                ),
            ),
            array(
                "369\r\n",
                array(
                    'command' => '369',
                    'code' => 'RPL_ENDOFWHOWAS',
                ),
            ),
            array(
                "321\r\n",
                array(
                    'command' => '321',
                    'code' => 'RPL_LISTSTART',
                ),
            ),
            array(
                "322\r\n",
                array(
                    'command' => '322',
                    'code' => 'RPL_LIST',
                ),
            ),
            array(
                "323\r\n",
                array(
                    'command' => '323',
                    'code' => 'RPL_LISTEND',
                ),
            ),
            array(
                "324\r\n",
                array(
                    'command' => '324',
                    'code' => 'RPL_CHANNELMODEIS',
                ),
            ),
            array(
                "331\r\n",
                array(
                    'command' => '331',
                    'code' => 'RPL_NOTOPIC',
                ),
            ),
            array(
                "332\r\n",
                array(
                    'command' => '332',
                    'code' => 'RPL_TOPIC',
                ),
            ),
            array(
                "341\r\n",
                array(
                    'command' => '341',
                    'code' => 'RPL_INVITING',
                ),
            ),
            array(
                "342\r\n",
                array(
                    'command' => '342',
                    'code' => 'RPL_SUMMONING',
                ),
            ),
            array(
                "351\r\n",
                array(
                    'command' => '351',
                    'code' => 'RPL_VERSION',
                ),
            ),
            array(
                "352\r\n",
                array(
                    'command' => '352',
                    'code' => 'RPL_WHOREPLY',
                ),
            ),
            array(
                "315\r\n",
                array(
                    'command' => '315',
                    'code' => 'RPL_ENDOFWHO',
                ),
            ),
            array(
                "353\r\n",
                array(
                    'command' => '353',
                    'code' => 'RPL_NAMREPLY',
                ),
            ),
            array(
                "366\r\n",
                array(
                    'command' => '366',
                    'code' => 'RPL_ENDOFNAMES',
                ),
            ),
            array(
                "364\r\n",
                array(
                    'command' => '364',
                    'code' => 'RPL_LINKS',
                ),
            ),
            array(
                "365\r\n",
                array(
                    'command' => '365',
                    'code' => 'RPL_ENDOFLINKS',
                ),
            ),
            array(
                "367\r\n",
                array(
                    'command' => '367',
                    'code' => 'RPL_BANLIST',
                ),
            ),
            array(
                "368\r\n",
                array(
                    'command' => '368',
                    'code' => 'RPL_ENDOFBANLIST',
                ),
            ),
            array(
                "371\r\n",
                array(
                    'command' => '371',
                    'code' => 'RPL_INFO',
                ),
            ),
            array(
                "374\r\n",
                array(
                    'command' => '374',
                    'code' => 'RPL_ENDOFINFO',
                ),
            ),
            array(
                "375\r\n",
                array(
                    'command' => '375',
                    'code' => 'RPL_MOTDSTART',
                ),
            ),
            array(
                "372\r\n",
                array(
                    'command' => '372',
                    'code' => 'RPL_MOTD',
                ),
            ),
            array(
                "376\r\n",
                array(
                    'command' => '376',
                    'code' => 'RPL_ENDOFMOTD',
                ),
            ),
            array(
                "381\r\n",
                array(
                    'command' => '381',
                    'code' => 'RPL_YOUREOPER',
                ),
            ),
            array(
                "382\r\n",
                array(
                    'command' => '382',
                    'code' => 'RPL_REHASHING',
                ),
            ),
            array(
                "391\r\n",
                array(
                    'command' => '391',
                    'code' => 'RPL_TIME',
                ),
            ),
            array(
                "392\r\n",
                array(
                    'command' => '392',
                    'code' => 'RPL_USERSSTART',
                ),
            ),
            array(
                "393\r\n",
                array(
                    'command' => '393',
                    'code' => 'RPL_USERS',
                ),
            ),
            array(
                "394\r\n",
                array(
                    'command' => '394',
                    'code' => 'RPL_ENDOFUSERS',
                ),
            ),
            array(
                "395\r\n",
                array(
                    'command' => '395',
                    'code' => 'RPL_NOUSERS',
                ),
            ),
            array(
                "200\r\n",
                array(
                    'command' => '200',
                    'code' => 'RPL_TRACELINK',
                ),
            ),
            array(
                "201\r\n",
                array(
                    'command' => '201',
                    'code' => 'RPL_TRACECONNECTING',
                ),
            ),
            array(
                "202\r\n",
                array(
                    'command' => '202',
                    'code' => 'RPL_TRACEHANDSHAKE',
                ),
            ),
            array(
                "203\r\n",
                array(
                    'command' => '203',
                    'code' => 'RPL_TRACEUNKNOWN',
                ),
            ),
            array(
                "204\r\n",
                array(
                    'command' => '204',
                    'code' => 'RPL_TRACEOPERATOR',
                ),
            ),
            array(
                "205\r\n",
                array(
                    'command' => '205',
                    'code' => 'RPL_TRACEUSER',
                ),
            ),
            array(
                "206\r\n",
                array(
                    'command' => '206',
                    'code' => 'RPL_TRACESERVER',
                ),
            ),
            array(
                "208\r\n",
                array(
                    'command' => '208',
                    'code' => 'RPL_TRACENEWTYPE',
                ),
            ),
            array(
                "261\r\n",
                array(
                    'command' => '261',
                    'code' => 'RPL_TRACELOG',
                ),
            ),
            array(
                "211\r\n",
                array(
                    'command' => '211',
                    'code' => 'RPL_STATSLINKINFO',
                ),
            ),
            array(
                "212\r\n",
                array(
                    'command' => '212',
                    'code' => 'RPL_STATSCOMMANDS',
                ),
            ),
            array(
                "213\r\n",
                array(
                    'command' => '213',
                    'code' => 'RPL_STATSCLINE',
                ),
            ),
            array(
                "214\r\n",
                array(
                    'command' => '214',
                    'code' => 'RPL_STATSNLINE',
                ),
            ),
            array(
                "215\r\n",
                array(
                    'command' => '215',
                    'code' => 'RPL_STATSILINE',
                ),
            ),
            array(
                "216\r\n",
                array(
                    'command' => '216',
                    'code' => 'RPL_STATSKLINE',
                ),
            ),
            array(
                "218\r\n",
                array(
                    'command' => '218',
                    'code' => 'RPL_STATSYLINE',
                ),
            ),
            array(
                "219\r\n",
                array(
                    'command' => '219',
                    'code' => 'RPL_ENDOFSTATS',
                ),
            ),
            array(
                "241\r\n",
                array(
                    'command' => '241',
                    'code' => 'RPL_STATSLLINE',
                ),
            ),
            array(
                "242\r\n",
                array(
                    'command' => '242',
                    'code' => 'RPL_STATSUPTIME',
                ),
            ),
            array(
                "243\r\n",
                array(
                    'command' => '243',
                    'code' => 'RPL_STATSOLINE',
                ),
            ),
            array(
                "244\r\n",
                array(
                    'command' => '244',
                    'code' => 'RPL_STATSHLINE',
                ),
            ),
            array(
                "221\r\n",
                array(
                    'command' => '221',
                    'code' => 'RPL_UMODEIS',
                ),
            ),
            array(
                "251\r\n",
                array(
                    'command' => '251',
                    'code' => 'RPL_LUSERCLIENT',
                ),
            ),
            array(
                "252\r\n",
                array(
                    'command' => '252',
                    'code' => 'RPL_LUSEROP',
                ),
            ),
            array(
                "253\r\n",
                array(
                    'command' => '253',
                    'code' => 'RPL_LUSERUNKNOWN',
                ),
            ),
            array(
                "254\r\n",
                array(
                    'command' => '254',
                    'code' => 'RPL_LUSERCHANNELS',
                ),
            ),
            array(
                "255\r\n",
                array(
                    'command' => '255',
                    'code' => 'RPL_LUSERME',
                ),
            ),
            array(
                "256\r\n",
                array(
                    'command' => '256',
                    'code' => 'RPL_ADMINME',
                ),
            ),
            array(
                "257\r\n",
                array(
                    'command' => '257',
                    'code' => 'RPL_ADMINLOC1',
                ),
            ),
            array(
                "258\r\n",
                array(
                    'command' => '258',
                    'code' => 'RPL_ADMINLOC2',
                ),
            ),
            array(
                "259\r\n",
                array(
                    'command' => '259',
                    'code' => 'RPL_ADMINEMAIL',
                ),
            ),
            array(
                "999\r\n",
                array(
                    'command' => '999',
                    'code' => '999',
                ),
            ),
            
            // ACTION (CTCP Specification)
            array(
                ":john!~jsmith@example.com PRIVMSG #test :\001ACTION test\001\r\n",
                array(
                    'prefix' => ':john!~jsmith@example.com',
                    'nick' => 'john',
                    'user' => '~jsmith',
                    'host' => 'example.com',
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'all' => "#test :\001ACTION test\001",
                        'receivers' => '#test',
                        'text' => "\001ACTION test\001",
                    ),
                    'message' => ":john!~jsmith@example.com PRIVMSG #test :\001ACTION test\001\r\n",
                    'targets' => Array (
                        '0' => '#test',
                    ),
                    'ctcp' => Array (
                        'command' => 'ACTION',
                        'params' => Array(
                            'all' => 'test',
                        ),
                    ),
                ),
            ),

            // FINGER (CTCP Specification)
            array(
                "PRIVMSG victim :\001FINGER\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001FINGER\001",
                        'all' => "victim :\001FINGER\001",
                    ),
                    'targets' => array('victim'),
                    'ctcp' => array(
                        'command' => 'FINGER',
                    ),
                ),
            ),

            array(
                ":victim NOTICE actor :\001FINGER :Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.\001\r\n",
                array(
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'actor',
                        'text' => "\001FINGER :Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.\001",
                        'all' => "actor :\001FINGER :Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.\001",
                    ),
                    'targets' => array('actor'),
                    'ctcp' => array(
                        'command' => 'FINGER',
                        'params' => array(
                            'user' => 'Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.',
                            'all' => ':Please check my USERINFO instead :Klaus Zeuge (sojge@mizar) 1 second has passed since victim gave a command last.'
                        ),
                    ),
                ),
            ),

            // VERSION (CTCP Specification)
            array(
                "PRIVMSG victim :\001VERSION\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001VERSION\001",
                        'all' => "victim :\001VERSION\001",
                    ),
                    'targets' => array('victim'),
                    'ctcp' => array(
                        'command' => 'VERSION',
                    ),
                ),
            ),

            array(
                ":victim NOTICE actor :\001VERSION Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README\001\r\n",
                array(
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'actor',
                        'text' => "\001VERSION Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README\001",
                        'all' => "actor :\001VERSION Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README\001",
                    ),
                    'targets' => array('actor'),
                    'ctcp' => array(
                        'command' => 'VERSION',
                        'params' => array(
                            'name' => 'Kiwi',
                            'version' => '5.2',
                            'environment' => 'GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README',
                            'all' => 'Kiwi:5.2:GNU Emacs 18.57.19 under SunOS 4.1.1 on Sun SLC:FTP.Lysator.LiU.SE:/pub/emacs Kiwi-5.2.el.Z Kiwi.README',
                        ),
                    ),
                )
            ),

            // SOURCE (CTCP Specification)
            array(
                "PRIVMSG victim :\001SOURCE cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001SOURCE cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z\001",
                        'all' => "victim :\001SOURCE cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z\001",
                    ),
                    'targets' => array('victim'),
                    'ctcp' => array(
                        'command' => 'SOURCE',
                        'params' => array(
                            'host' => 'cs.bu.edu',
                            'directories' => '/pub/irc',
                            'files' => 'Kiwi.5.2.el.Z',
                            'all' => 'cs.bu.edu:/pub/irc:Kiwi.5.2.el.Z',
                        ),
                    ),
                ),
            ),

            // USERINFO (CTCP Specification)
            array(
                "PRIVMSG victim :\001USERINFO\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001USERINFO\001",
                        'all' => "victim :\001USERINFO\001",
                    ),
                    'targets' => array('victim'),
                    'ctcp' => array(
                        'command' => 'USERINFO',
                    ),
                ),
            ),

            array(
                ":victim NOTICE actor :\001USERINFO :I'm studying computer science in Uppsala, I'm male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.\001\r\n",
                array(
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'actor',
                        'text' => "\001USERINFO :I'm studying computer science in Uppsala, I'm male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.\001",
                        'all' => "actor :\001USERINFO :I'm studying computer science in Uppsala, I'm male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.\001",
                    ),
                    'targets' => array('actor'),
                    'ctcp' => array(
                        'command' => 'USERINFO',
                        'params' => array(
                            'user' => 'I\'m studying computer science in Uppsala, I\'m male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.',
                            'all' => ':I\'m studying computer science in Uppsala, I\'m male (somehow, that seems to be an important matter on IRC:-) and I speak fluent swedish, decent german, and some english.',
                        ),
                    ),
                ),
            ),

            // CLIENTINFO (CTCP Specification)
            array(
                "PRIVMSG victim :\001CLIENTINFO\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001CLIENTINFO\001",
                        'all' => "victim :\001CLIENTINFO\001",
                    ),
                    'targets' => array('victim'),
                    'ctcp' => array(
                        'command' => 'CLIENTINFO',
                    ),
                ),
            ),

            array(
                ":victim NOTICE actor :\001CLIENTINFO :You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.\001\r\n",
                array(
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'actor',
                        'text' => "\001CLIENTINFO :You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.\001",
                        'all' => "actor :\001CLIENTINFO :You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.\001",
                    ),
                    'targets' => array('actor'),
                    'ctcp' => array(
                        'command' => 'CLIENTINFO',
                        'params' => array(
                            'client' => 'You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.',
                            'all' => ':You can request help of the commands CLIENTINFO ERRMSG FINGER USERINFO VERSION by giving an argument to CLIENTINFO.',
                        ),
                    ),
                ),
            ),

            // ERRMSG (CTCP Specification)
            array(
                ":victim NOTICE actor :\001ERRMSG clientinfo clientinfo :Query is unknown\001\r\n",
                array(
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'actor',
                        'text' => "\001ERRMSG clientinfo clientinfo :Query is unknown\001",
                        'all' => "actor :\001ERRMSG clientinfo clientinfo :Query is unknown\001",
                    ),
                    'targets' => array('actor'),
                    'ctcp' => array(
                        'command' => 'ERRMSG',
                        'params' => array(
                            'query' => 'clientinfo clientinfo',
                            'message' => 'Query is unknown',
                            'all' => 'clientinfo clientinfo :Query is unknown',
                        ),
                    ),
                ),
            ),

            // PING (CTCP Specification)
            array(
                "PRIVMSG victim :\001PING 1350742705\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001PING 1350742705\001",
                        'all' => "victim :\001PING 1350742705\001",
                    ),
                    'targets' => array('victim'),
                    'ctcp' => array(
                        'command' => 'PING',
                        'params' => array(
                            'timestamp' => '1350742705',
                            'all' => '1350742705',
                        ),
                    ),
                ),
            ),

            array(
                ":victim NOTICE actor :\001PING 1350742748\001\r\n",
                array(
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'actor',
                        'text' => "\001PING 1350742748\001",
                        'all' => "actor :\001PING 1350742748\001",
                    ),
                    'targets' => array('actor'),
                    'ctcp' => array(
                        'command' => 'PING',
                        'params' => array(
                            'timestamp' => '1350742748',
                            'all' => '1350742748',
                        ),
                    ),
                )
            ),

            // TIME (CTCP Specification)
            array(
                "PRIVMSG victim :\001TIME\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001TIME\001",
                        'all' => "victim :\001TIME\001",
                    ),
                    'targets' => array('victim'),
                    'ctcp' => array(
                        'command' => 'TIME',
                    ),
                ),
            ),

            array(
                ":victim NOTICE actor :\001TIME :Thu Aug 11 22:52:51 1994 CST\001\r\n",
                array(
                    'prefix' => ':victim',
                    'nick' => 'victim',
                    'command' => 'NOTICE',
                    'params' => array(
                        'nickname' => 'actor',
                        'text' => "\001TIME :Thu Aug 11 22:52:51 1994 CST\001",
                        'all' => "actor :\001TIME :Thu Aug 11 22:52:51 1994 CST\001",
                    ),
                    'targets' => array('actor'),
                    'ctcp' => array(
                        'command' => 'TIME',
                        'params' => array(
                            'time' => 'Thu Aug 11 22:52:51 1994 CST',
                            'all' => ':Thu Aug 11 22:52:51 1994 CST',
                        ),
                    ),
                )
            ),

            // Malformed CTCP command
            array(
                "PRIVMSG victim :\001ACTIONlooks\001\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'victim',
                        'text' => "\001ACTIONlooks\001",
                        'all' => "victim :\001ACTIONlooks\001",
                    ),
                    'targets' => array('victim'),
                    'message' => "PRIVMSG victim :\001ACTIONlooks\001\r\n",
                    'ctcp' => array(
                        'command' => 'ACTIONlooks'
                    ),
                ),
            ),

            // Individual NUL, CR, or LF characters are stripped out
            array(
                ":server.name 372 BotNick :Who left a null byte \0 in here?\r\n",
                array(
                    'prefix' => ':server.name',
                    'servername' => 'server.name',
                    'command' => '372',
                    'params' => array(
                        1 => 'Who left a null byte  in here?',
                        'iterable' => array(),
                        'tail' => 'Who left a null byte  in here?',
                        'all' => ':Who left a null byte  in here?',
                    ),
                    'code' => 'RPL_MOTD',
                    'target' => 'BotNick',
                    'message' => ":server.name 372 BotNick :Who left a null byte  in here?\r\n",
                ),
            ),

            array(
                ":server.name 372 BotNick :Who left a carriage return \r in here?\r\n",
                array(
                    'prefix' => ':server.name',
                    'servername' => 'server.name',
                    'command' => '372',
                    'params' => array(
                        1 => 'Who left a carriage return  in here?',
                        'iterable' => array(),
                        'tail' => 'Who left a carriage return  in here?',
                        'all' => ':Who left a carriage return  in here?',
                    ),
                    'code' => 'RPL_MOTD',
                    'target' => 'BotNick',
                    'message' => ":server.name 372 BotNick :Who left a carriage return  in here?\r\n",
                ),
            ),

            array(
                ":server.name 372 BotNick :Who left a line feed \n in here?\r\n",
                array(
                    'prefix' => ':server.name',
                    'servername' => 'server.name',
                    'command' => '372',
                    'params' => array(
                        1 => 'Who left a line feed  in here?',
                        'iterable' => array(),
                        'tail' => 'Who left a line feed  in here?',
                        'all' => ':Who left a line feed  in here?',
                    ),
                    'code' => 'RPL_MOTD',
                    'target' => 'BotNick',
                    'message' => ":server.name 372 BotNick :Who left a line feed  in here?\r\n",
                ),
            ),

            // Freenode doesn't properly demarcate trailing command parameters in some cases
            array(
                ":pratchett.freenode.net 004 Phergie3 pratchett.freenode.net ircd-seven-1.1.3 DOQRSZaghilopswz CFILMPQbcefgijklmnopqrstvz bkloveqjfI\r\n",
                array(
                    'prefix' => ':pratchett.freenode.net',
                    'servername' => 'pratchett.freenode.net',
                    'command' => '004',
                    'params' => array(
                        1 => 'pratchett.freenode.net',
                        2 => 'ircd-seven-1.1.3',
                        3 => 'DOQRSZaghilopswz',
                        4 => 'CFILMPQbcefgijklmnopqrstvz',
                        5 => 'bkloveqjfI',
                        'iterable' => array(
                            'pratchett.freenode.net',
                            'ircd-seven-1.1.3',
                            'DOQRSZaghilopswz',
                            'CFILMPQbcefgijklmnopqrstvz',
                            'bkloveqjfI',
                        ),
                        'all' => 'pratchett.freenode.net ircd-seven-1.1.3 DOQRSZaghilopswz CFILMPQbcefgijklmnopqrstvz bkloveqjfI',
                    ),
                    'code' => '004',
                    'target' => 'Phergie3',
                ),
            ),

            // Freenode uses an invalid hostname in some server responses
            array(
                ":services. 328 Phergie3 #laravel :http://laravel.com\r\n",
                array(
                    'prefix' => ':services.',
                    'servername' => 'services.',
                    'command' => '328',
                    'params' => array(
                        1 => '#laravel',
                        2 => 'http://laravel.com',
                        'iterable' => array('#laravel'),
                        'tail' => 'http://laravel.com',
                        'all' => '#laravel :http://laravel.com',
                    ),
                    'message' => ":services. 328 Phergie3 #laravel :http://laravel.com\r\n",
                    'code' => '328',
                    'target' => 'Phergie3',
                ),
            ),

            // Freenode doesn't prefix the PART channels parameter with a colon
            array(
                ":julien-c!~julien-c@tru75-6-82-240-32-161.fbx.proxad.net PART #laravel\r\n",
                array(
                    'prefix' => ':julien-c!~julien-c@tru75-6-82-240-32-161.fbx.proxad.net',
                    'nick' => 'julien-c',
                    'user' => '~julien-c',
                    'host' => 'tru75-6-82-240-32-161.fbx.proxad.net',
                    'command' => 'PART',
                    'params' => array(
                        'channels' => '#laravel',
                        'all' => '#laravel',
                    ),
                    'targets' => array('#laravel'),
                    'message' => ":julien-c!~julien-c@tru75-6-82-240-32-161.fbx.proxad.net PART #laravel\r\n",
                ),
            ),

            // Rizon allows nicks valid under RFC 2812, but not RFC 1459
            array(
                "PRIVMSG ____ :test\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => '____',
                        'text' => 'test',
                        'all' => '____ :test',
                    ),
                    'targets' => array('____'),
                ),
            ),

            array(
                "PRIVMSG |blah :test\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => '|blah',
                        'text' => 'test',
                        'all' => '|blah :test',
                    ),
                    'targets' => array('|blah'),
                ),
            ),

            array(
                "PRIVMSG hello|there :test\r\n",
                array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'hello|there',
                        'text' => 'test',
                        'all' => 'hello|there :test',
                    ),
                    'targets' => array('hello|there'),
                ),
            ),

            // Hostname/ident patterns
            array(
                ":nick!ident@123.host.com PRIVMSG target :message\r\n",
                array(
                    'prefix' => ':nick!ident@123.host.com',
                    'nick' => 'nick',
                    'user' => 'ident',
                    'host' => '123.host.com',
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'all' => 'target :message',
                        'receivers' => 'target',
                        'text' => 'message',
                    ),
                    'targets' => array('target'),
                ),
            ),

            array(
                ":nick!ident- PRIVMSG target :message\r\n",
                array(
                    'prefix' => ':nick!ident-',
                    'nick' => 'nick',
                    'user' => 'ident-',
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'all' => 'target :message',
                        'receivers' => 'target',
                        'text' => 'message',
                    ),
                    'targets' => array('target'),
                ),
            ),

            array(
                ":nick!ident@- PRIVMSG target :message\r\n",
                array(
                    'invalid' => ":nick!ident@- PRIVMSG target :message\r\n",
                ),
            ),

            array(
                ":nick!ident@localhost PRIVMSG target :message\r\n",
                array(
                    'prefix' => ':nick!ident@localhost',
                    'nick' => 'nick',
                    'user' => 'ident',
                    'host' => 'localhost',
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'all' => 'target :message',
                        'receivers' => 'target',
                        'text' => 'message',
                    ),
                    'targets' => array('target'),
                ),
            ),

            // Check that the string '0' is not filtered out from a params list
            array(
                "USER myident 0 * :Ronnie Reagan\r\n",
                array(
                    'command' => 'USER',
                    'params' => array(
                        'all' => 'myident 0 * :Ronnie Reagan',
                        'username' => 'myident',
                        'hostname' => '0',
                        'servername' => '*',
                        'realname' => 'Ronnie Reagan',
                    ),
                    'targets' => array('myident'),
                ),
            ),
        );

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
        $message1 = array(
            'string' => ":Angel PRIVMSG Wiz :Hello are you receiving this message ?",
            'parsed' => array(
                    'prefix' => ':Angel',
                    'nick' => 'Angel',
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'Wiz',
                        'text' => 'Hello are you receiving this message ?',
                        'all' => 'Wiz :Hello are you receiving this message ?',
                    ),
                    'targets' => array('Wiz'),
                ),
            );

        $message2 = array(
            'string' => "PRIVMSG Angel :yes I'm receiving it !receiving it !'u>(768u+1n) .br",
            'parsed' => array(
                    'command' => 'PRIVMSG',
                    'params' => array(
                        'receivers' => 'Angel',
                        'text' => 'yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                        'all' => 'Angel :yes I\'m receiving it !receiving it !\'u>(768u+1n) .br',
                    ),
                    'targets' => array('Angel'),
                ),
            );

        $data = array();

        // No messages
        $message = '';
        $data[] = array($message, array());

        // One incomplete message
        $message .= $message1['string'];
        $data[] = array($message, array());

        // One complete message
        $message .= "\r\n";
        $expected = $message1['parsed'];
        $expected['message'] = $message;
        unset($expected['tail']);
        $data[] = array($message, array($expected));

        // One complete message, one incomplete message
        $message .= $message2['string'];
        $expected['tail'] = $message2['string'];
        $data[] = array($message, array($expected));

        // Two complete messages
        $message .= "\r\n";
        $message1['parsed']['message'] = $message1['string'] . "\r\n";
        $message2['parsed']['message'] = $message1['parsed']['tail'] = $message2['string'] . "\r\n";
        $data[] = array($message, array($message1['parsed'], $message2['parsed']));

        return $data;
    }
}
