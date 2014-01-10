function getTypeOf(obj) {
    var typ = typeof obj;
    switch (typ) {
        case 'object':
            if (obj == null) {
                return 'null';
            }
            if (obj instanceof  Array) {
                return 'array';
            }
            if (obj instanceof Date) {
                return 'date';
            }
            if (obj instanceof RegExp) {
                return 'regexp';
            }
            return 'object';
            break;
        case 'function':
            break;
        case 'undefined':
            break;
        default:
            break;
    }
    return typ;
}


function getJsonString(o) {
    var pieces = new Array();

    if (arguments[3] != undefined) {
        pieces.push(arguments[3]);
    }

    var indent = 0;
    if (arguments[2] != undefined) {
        indent = arguments[2];
    }
    pieces.push('<p style="padding:0; margin:0 0 0 ');
    pieces.push(indent);
    pieces.push('em;">');

    if (arguments[1] != undefined && arguments[1] != null) {
        pieces.push('"');
        pieces.push(arguments[1]);
        pieces.push('":');
    }
    var typ = getTypeOf(o);
    switch (typ) {
        case 'number':
            pieces.push('<span style="color:red">');
            pieces.push(o.toString());
            pieces.push('</span>');
            pieces.push('</p>');
            break;
        case 'boolean':
            pieces.push('<span style="color:purple">');
            pieces.push(o.toString());
            pieces.push('</span>');
            pieces.push('</p>');
            break;
        case 'string':
            pieces.push('<span style="color:green">');
            var s = htmlspecialchars(JSON.stringify(o), 'ENT_QUOTES')
            pieces.push(s);
            pieces.push('</span>');
            pieces.push('</p>');
            break;
        case 'date':
        case 'regexp':
        case 'function':
            pieces.push('<span style="color:green">');
            pieces.push('"');
            var s = htmlspecialchars(o.toString(), 'ENT_QUOTES')
            pieces.push(s);
            pieces.push('"');
            pieces.push('</span>');
            pieces.push('</p>');
            break;
        case 'null':
        case 'undefined':
            pieces.push('<span style="color:darkgrey">');
            pieces.push('null');
            pieces.push('</span>');
            pieces.push('</p>');
            break;
        case 'object':
            pieces.push('{</p>');
            var ch = '';
            for (str in o) {
                pieces.push(getJsonString(o[str], str, indent+2, ch));
                ch = ',';
            }
            pieces.push('<p style="padding:0; margin:0 0 0 ');
            pieces.push(indent);
            pieces.push('em;">');
            pieces.push('}</p>');
            break;
        case 'array':
            pieces.push('[</p>');
            var ch = '';
            for (str in o) {
                pieces.push(getJsonString(o[str], null, indent+2, ch));
                ch = ',';
            }
            pieces.push('<p style="padding:0; margin:0 0 0 ');
            pieces.push(indent);
            pieces.push('em;">]</p>');
            break;
    }
    var s = pieces.join('');
    return s;
}

function getJsonHtml(jsonObj) {
    var html = getJsonString(jsonObj, null, 0);
    html = html.replace(/<\/p>,/gm, ',</p>');
    return html;
}

function htmlspecialchars(string, quote_style, charset, double_encode) {
    // http://kevin.vanzonneveld.net
    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);
    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'
    var optTemp = 0,
        i = 0,
        noquotes = false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE': 1,
        'ENT_HTML_QUOTE_DOUBLE': 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE': 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes) {
        string = string.replace(/"/g, '&quot;');
    }

    return string;
}
