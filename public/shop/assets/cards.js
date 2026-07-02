/* VowNook Shop — shared stationery canvas engine.
   Renders any card of the collection onto a 1500×2100 canvas (5×7in @300dpi).
   Used by the product pages (live "see it with your names" previews) and by
   customize.html (the full personaliser). Keep renderers in sync with the
   sold Canva templates' look. */
(function () {
  'use strict';

  var CW = { Champagne: '#a07c33', Sage: '#6f7e62', Blush: '#b07a74' };
  var NOIRCW = { Champagne: '#c9a86a', Sage: '#9cae8c', Blush: '#cda59e' };
  var DESIGNS = ['Heirloom', 'Editorial', 'Arch', 'Deco', 'Script', 'Botanical', 'Monogram', 'Minimal', 'Bold', 'Noir'];
  var CARDS = [
    { id: 'inv', label: 'Invitation' },
    { id: 'std', label: 'Save the Date' },
    { id: 'det', label: 'Details' },
    { id: 'rsvp', label: 'RSVP' },
    { id: 'menu', label: 'Menu' },
    { id: 'welcome', label: 'Welcome' },
    { id: 'timeline', label: 'Order of Day' },
    { id: 'placecard', label: 'Place Card' },
    { id: 'tableno', label: 'Table No.' },
    { id: 'thankyou', label: 'Thank You' },
    { id: 'vowcover', label: 'Vow Book' },
  ];
  var DEFAULTS = {
    p1: 'Olivia', p2: 'James',
    date: 'June 12, 2027', bigDate: '12.06.27',
    d1: 'Saturday, the twelfth of June', d2: 'Two thousand and twenty-seven', d3: "At four o'clock in the afternoon",
    venue: 'The Vineyard Estate', loc: 'Prince Edward County, Ontario',
    rsvpBy: 'the first of May', web: 'oliviaandjames.vownook.com',
    menu1: 'Heirloom tomato & burrata', menu2: 'Roast chicken or salmon', menu3: 'Vanilla bean panna cotta',
    welcomeMsg: 'Find your seat & celebrate with us', guestName: 'Aunt Rose', tableNo: 'Four',
  };

  var S = 1500 / 360;

  function render(canvas, st, dataIn) {
    var ctx = canvas.getContext('2d');
    var data = {};
    for (var k in DEFAULTS) data[k] = (dataIn && dataIn[k] != null && dataIn[k] !== '') ? dataIn[k] : DEFAULTS[k];
    var D = function () { return data; };

    var INK = '#1b1916', MUT = '#6f665b', PAPER = '#ffffff';
    var UP = function (s) { return String(s).toUpperCase(); };

    function ct(s, top, weight, family, size, color, cs, cxf) {
      var cx = (cxf || 0.5) * 1500; ctx.fillStyle = color; ctx.font = weight + ' ' + (size * S) + "px '" + family + "'"; ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic';
      if (cs) {
        var chs = Array.from(String(s)); ctx.textAlign = 'left';
        var ws = chs.map(function (c) { return ctx.measureText(c).width; });
        var total = ws.reduce(function (a, b) { return a + b; }, 0) + cs * S * (chs.length - 1); var x = cx - total / 2;
        chs.forEach(function (c, i) { ctx.fillText(c, x, top * S); x += ws[i] + cs * S; }); ctx.textAlign = 'center';
      } else ctx.fillText(s, cx, top * S);
    }
    function sn(top, size, g, p1, p2, variant) {
      var d = D(); p1 = p1 || d.p1; p2 = p2 || d.p2; variant = variant || 'serif';
      if (variant === 'caps') { p1 = UP(p1); p2 = UP(p2); }
      var sz = (variant === 'caps' ? size * 0.72 : size) * S, y = top * S, gap = sz * (variant === 'caps' ? 0.42 : 0.30);
      var nf = variant === 'italic' ? 'italic ' + sz + 'px Fraunces' : '600 ' + sz + 'px Fraunces', af = 'italic ' + (sz * 0.82) + 'px Fraunces';
      ctx.textBaseline = 'alphabetic';
      ctx.font = nf; var w1 = ctx.measureText(p1).width;
      ctx.font = af; var wa = ctx.measureText('&').width;
      ctx.font = nf; var w3 = ctx.measureText(p2).width;
      var x = 750 - (w1 + gap + wa + gap + w3) / 2; ctx.textAlign = 'left';
      ctx.fillStyle = INK; ctx.font = nf; ctx.fillText(p1, x, y); x += w1 + gap;
      ctx.fillStyle = g; ctx.font = af; ctx.fillText('&', x, y); x += wa + gap;
      ctx.fillStyle = INK; ctx.font = nf; ctx.fillText(p2, x, y); ctx.textAlign = 'center';
    }
    function drule(top, g, half) {
      half = (half || 46) * S; var y = top * S, d = 2.6 * S; ctx.strokeStyle = g; ctx.lineWidth = Math.max(1, 0.6 * S);
      ctx.beginPath(); ctx.moveTo(750 - half, y); ctx.lineTo(750 - d * 2, y); ctx.moveTo(750 + d * 2, y); ctx.lineTo(750 + half, y); ctx.stroke();
      ctx.save(); ctx.translate(750, y); ctx.rotate(Math.PI / 4); ctx.fillStyle = g; ctx.fillRect(-d, -d, 2 * d, 2 * d); ctx.restore();
    }
    function bg() { ctx.fillStyle = PAPER; ctx.fillRect(0, 0, 1500, 2100); }

    /* frames */
    function fHeirloom(g) { var ins = 1500 * 0.062; ctx.strokeStyle = g; ctx.lineWidth = 1.1 * S; ctx.strokeRect(ins, ins, 1500 - 2 * ins, 2100 - 2 * ins); ctx.lineWidth = 0.4 * S; ctx.strokeRect(ins + 5 * S, ins + 5 * S, 1500 - 2 * ins - 10 * S, 2100 - 2 * ins - 10 * S); }
    function fEditorial(g) { var m = 1500 * 0.12; ctx.strokeStyle = g; ctx.lineWidth = 0.6 * S; ctx.beginPath(); ctx.moveTo(m, 64 * S); ctx.lineTo(1500 - m, 64 * S); ctx.moveTo(m, 440 * S); ctx.lineTo(1500 - m, 440 * S); ctx.stroke(); }
    function fArch(g) {
      var left = 255, right = 1245, cx = 750, r = 495, spring = 182.8 * S, base = 472 * S; ctx.strokeStyle = g; ctx.lineWidth = 1.0 * S;
      ctx.beginPath(); ctx.moveTo(left, base); ctx.lineTo(left, spring);
      for (var i = 0; i <= 60; i++) { var th = Math.PI - Math.PI * i / 60; ctx.lineTo(cx + r * Math.cos(th), spring - r * Math.sin(th)); }
      ctx.lineTo(right, base); ctx.stroke();
    }
    function fDeco(g) {
      var ins = 1500 * 0.085; ctx.strokeStyle = g; ctx.lineWidth = 0.8 * S; ctx.strokeRect(ins, ins, 1500 - 2 * ins, 2100 - 2 * ins); ctx.strokeRect(ins + 7 * S, ins + 7 * S, 1500 - 2 * ins - 14 * S, 2100 - 2 * ins - 14 * S);
      var s = 10 * S; [[ins, ins], [1500 - ins, ins], [ins, 2100 - ins], [1500 - ins, 2100 - ins]].forEach(function (p) { ctx.save(); ctx.translate(p[0], p[1]); ctx.rotate(Math.PI / 4); ctx.fillStyle = g; ctx.fillRect(-s / 2, -s / 2, s, s); ctx.restore(); });
    }
    function leaf(x, y, ln, ang, color) { ctx.save(); ctx.translate(x, y); ctx.rotate(ang); ctx.fillStyle = color; ctx.beginPath(); ctx.moveTo(0, 0); ctx.bezierCurveTo(ln * 0.4, ln * 0.32, ln * 0.8, ln * 0.28, ln, 0); ctx.bezierCurveTo(ln * 0.8, -ln * 0.28, ln * 0.4, -ln * 0.32, 0, 0); ctx.fill(); ctx.restore(); }
    function sprig(cx, cy, scale, color, flip) {
      ctx.strokeStyle = color; ctx.lineWidth = 0.8 * S; ctx.beginPath(); ctx.moveTo(cx, cy);
      var ey = cy + 40 * scale * flip * S; ctx.bezierCurveTo(cx + 10 * scale * S, cy + 14 * scale * flip * S, cx - 8 * scale * S, cy + 28 * scale * flip * S, cx, ey); ctx.stroke();
      for (var i = 1; i <= 5; i++) { var t = i / 6; var ly = cy + 40 * scale * flip * S * t; var lx = cx + 6 * scale * S * Math.sin(t * 3); leaf(lx, ly, 11 * scale * S, (35 * flip) * Math.PI / 180, color); leaf(lx, ly, 11 * scale * S, (180 - 35 * flip) * Math.PI / 180, color); }
    }
    function fBotanical(g) { sprig(750, 50 * S, 1.0, g, 1); sprig(750, 468 * S, 0.85, g, -1); }

    /* bespoke invitations */
    function invHeirloom(g) {
      bg(); fHeirloom(g); ct('TOGETHER WITH THEIR FAMILIES', 78, '500', 'DM Sans', 7.6, g, 2.6); sn(150, 33, g);
      ct('request the pleasure of your company', 186, 'italic', 'Fraunces', 11.5, MUT); ct('as they celebrate their marriage', 208, 'italic', 'Fraunces', 11.5, MUT);
      drule(242, g); var d = D(); ct(UP(d.d1), 286, '400', 'DM Sans', 8.6, INK, 1.8); ct(UP(d.d2), 304, '400', 'DM Sans', 8.6, INK, 1.8); ct(UP(d.d3), 322, '400', 'DM Sans', 8.6, INK, 1.8);
      ct(d.venue, 380, '400', 'Fraunces', 14, INK); ct(d.loc, 400, '400', 'DM Sans', 8.6, MUT, 1); ct('Dinner & dancing to follow', 452, 'italic', 'Fraunces', 10, MUT);
    }
    function invEditorial(g) {
      bg(); fEditorial(g); var d = D(); ct(UP(d.p1 + '  &  ' + d.p2), 104, '500', 'DM Sans', 9, INK, 5); ct('ARE GETTING MARRIED', 124, '400', 'DM Sans', 7.5, MUT, 3);
      ct(d.bigDate, 250, '600', 'Fraunces', 74, INK); ct('SATURDAY AT FOUR IN THE AFTERNOON', 300, '400', 'DM Sans', 8, MUT, 2.6); drule(340, g, 38);
      ct(d.venue, 392, '400', 'Fraunces', 15, INK); ct(d.loc, 412, '400', 'DM Sans', 8.5, MUT, 1); ct('DINNER · DANCING · CELEBRATION', 470, '400', 'DM Sans', 7, g, 2.6);
    }
    function invArch(g) {
      bg(); fArch(g); var d = D(); ct('THE WEDDING OF', 145, '500', 'DM Sans', 7.4, g, 2.8); sn(200, 30, g);
      ct('request the honour of your presence', 236, 'italic', 'Fraunces', 10.5, MUT); drule(264, g, 40);
      ct(UP(d.d1), 298, '400', 'DM Sans', 8, INK, 1.6); ct(UP(d.d3), 314, '400', 'DM Sans', 8, INK, 1.6); ct(d.venue, 356, '400', 'Fraunces', 13, INK);
      ct(d.loc + ' · ' + d.date, 434, '400', 'DM Sans', 8, MUT, 1);
    }
    function invDeco(g) {
      bg(); fDeco(g); var d = D(); ct('TOGETHER WITH THEIR FAMILIES', 96, '500', 'DM Sans', 7, g, 3);
      ct(UP(d.p1), 148, '600', 'Fraunces', 30, INK, 4); ctx.strokeStyle = g; ctx.lineWidth = 1 * S;[[-9, 14], [0, 20], [9, 14]].forEach(function (v) { ctx.beginPath(); ctx.moveTo(750 + v[0] * S, 176 * S - v[1] / 2 * S); ctx.lineTo(750 + v[0] * S, 176 * S + v[1] / 2 * S); ctx.stroke(); });
      ct(UP(d.p2), 222, '600', 'Fraunces', 30, INK, 4); ct('REQUEST THE PLEASURE OF YOUR COMPANY', 272, '400', 'DM Sans', 7, MUT, 1.8);
      ct(UP(d.d1), 314, '400', 'DM Sans', 8, INK, 2); ct(UP(d.d3), 332, '400', 'DM Sans', 8, INK, 2); ct(UP(d.venue), 388, '500', 'DM Sans', 9, g, 2.4); ct(UP(d.loc), 408, '400', 'DM Sans', 7.5, MUT, 2);
      ct('DINNER & DANCING TO FOLLOW', 456, '400', 'DM Sans', 7, MUT, 2.4);
    }
    function invScript(g) {
      bg(); var d = D(); ct('TOGETHER WITH THEIR FAMILIES', 92, '500', 'DM Sans', 7.2, g, 2.8);
      ct(d.p1, 180, 'italic', 'Fraunces', 58, INK); ct('and', 220, 'italic', 'Fraunces', 22, g); ct(d.p2, 290, 'italic', 'Fraunces', 58, INK);
      ctx.strokeStyle = g; ctx.lineWidth = 0.6 * S; ctx.beginPath(); ctx.moveTo(750 - 44 * S, 326 * S); ctx.lineTo(750 + 44 * S, 326 * S); ctx.stroke();
      ct('REQUEST THE PLEASURE OF YOUR COMPANY', 366, '400', 'DM Sans', 7, MUT, 1.8); ct(UP(d.d1), 402, '400', 'DM Sans', 8, INK, 2); ct(UP(d.d3), 420, '400', 'DM Sans', 8, INK, 2);
      ct(d.venue + ' · ' + d.loc, 462, '400', 'Fraunces', 10.5, INK);
    }
    function invBotanical(g) {
      bg(); fBotanical(g); var d = D(); ct('the wedding of', 138, 'italic', 'Fraunces', 12, MUT); sn(198, 32, g);
      ct('request the pleasure of your company', 236, 'italic', 'Fraunces', 11, MUT); drule(270, g, 40);
      ct(UP(d.d1), 312, '400', 'DM Sans', 8, INK, 1.6); ct(UP(d.d3), 329, '400', 'DM Sans', 8, INK, 1.6); ct(d.venue, 378, '400', 'Fraunces', 13, INK); ct(d.loc, 398, '400', 'DM Sans', 8, MUT, 1);
    }
    function ringMono(cx, cy, r, g, i1, i2) {
      ctx.strokeStyle = g; ctx.lineWidth = 0.9 * S; ctx.beginPath(); ctx.arc(cx, cy, r, 0, 7); ctx.stroke();
      var size = 20 * S, gap = size * 0.3; ctx.textBaseline = 'alphabetic'; ctx.font = '600 ' + size + 'px Fraunces'; var w1 = ctx.measureText(i1).width; ctx.font = 'italic ' + (size * 0.8) + 'px Fraunces'; var wa = ctx.measureText('&').width; ctx.font = '600 ' + size + 'px Fraunces'; var w3 = ctx.measureText(i2).width;
      var x = cx - (w1 + gap + wa + gap + w3) / 2, y = cy + size * 0.34; ctx.textAlign = 'left';
      ctx.fillStyle = INK; ctx.font = '600 ' + size + 'px Fraunces'; ctx.fillText(i1, x, y); x += w1 + gap; ctx.fillStyle = g; ctx.font = 'italic ' + (size * 0.8) + 'px Fraunces'; ctx.fillText('&', x, y); x += wa + gap; ctx.fillStyle = INK; ctx.font = '600 ' + size + 'px Fraunces'; ctx.fillText(i2, x, y); ctx.textAlign = 'center';
    }
    function invMonogram(g) {
      bg(); var d = D(); ringMono(750, 110 * S, 34 * S, g, (d.p1[0] || 'O'), (d.p2[0] || 'J'));
      ct('THE WEDDING OF', 186, '500', 'DM Sans', 7.4, g, 2.8); sn(234, 28, g);
      ct('request the pleasure of your company', 268, 'italic', 'Fraunces', 10.5, MUT); drule(296, g, 40);
      ct(UP(d.d1), 330, '400', 'DM Sans', 8, INK, 1.6); ct(UP(d.d3), 346, '400', 'DM Sans', 8, INK, 1.6);
      ct(d.venue, 392, '400', 'Fraunces', 13, INK); ct(d.loc, 410, '400', 'DM Sans', 8, MUT, 1); ct('Dinner & dancing to follow', 456, 'italic', 'Fraunces', 9.5, MUT);
    }
    function invMinimal(g) {
      bg(); var d = D(); ct('THE WEDDING OF', 122, '500', 'DM Sans', 7, g, 3.4); sn(198, 30, g);
      ctx.strokeStyle = g; ctx.lineWidth = 0.5 * S; ctx.beginPath(); ctx.moveTo(750 - 26 * S, 234 * S); ctx.lineTo(750 + 26 * S, 234 * S); ctx.stroke();
      ct(UP(d.d1), 288, '400', 'DM Sans', 7.5, INK, 1.8); ct(UP(d.d3), 306, '400', 'DM Sans', 7.5, INK, 1.8);
      ct(d.venue, 362, '400', 'Fraunces', 12.5, INK); ct(d.loc, 380, '400', 'DM Sans', 7.5, MUT, 1); ct(d.date, 452, 'italic', 'Fraunces', 9.5, MUT);
    }
    function invBold(g) {
      bg(); var d = D(); ct('TOGETHER WITH THEIR FAMILIES', 96, '500', 'DM Sans', 7, g, 2.8);
      ct(UP(d.p1), 196, '600', 'Fraunces', 54, INK); ct('AND', 238, '500', 'DM Sans', 9, g, 4); ct(UP(d.p2), 300, '600', 'Fraunces', 54, INK);
      ctx.strokeStyle = g; ctx.lineWidth = 0.6 * S; ctx.beginPath(); ctx.moveTo(750 - 44 * S, 338 * S); ctx.lineTo(750 + 44 * S, 338 * S); ctx.stroke();
      ct(UP(d.d1), 382, '400', 'DM Sans', 8, MUT, 2); ct(UP(d.d3), 400, '400', 'DM Sans', 8, MUT, 2); ct(d.venue + ' · ' + d.loc, 450, '400', 'Fraunces', 10.5, INK);
    }
    function archTop(g) { var cx = 750, r = 520, spring = 175 * S, left = 230; ctx.strokeStyle = g; ctx.lineWidth = 1.0 * S; ctx.beginPath(); ctx.moveTo(left, spring); for (var i = 0; i <= 60; i++) { var th = Math.PI - Math.PI * i / 60; ctx.lineTo(cx + r * Math.cos(th), spring - r * Math.sin(th)); } ctx.stroke(); }
    function ringTop(g) { var d = D(); ringMono(750, 58 * S, 24 * S, g, (d.p1[0] || 'O'), (d.p2[0] || 'J')); }

    var INV = { Heirloom: invHeirloom, Editorial: invEditorial, Arch: invArch, Deco: invDeco, Script: invScript, Botanical: invBotanical, Monogram: invMonogram, Minimal: invMinimal, Bold: invBold, Noir: invHeirloom };
    var KIT = {
      Heirloom: { frame: fHeirloom, names: 'serif' }, Editorial: { frame: fEditorial, names: 'serif' }, Arch: { frame: archTop, names: 'serif' }, Deco: { frame: fDeco, names: 'caps' },
      Script: { frame: function () {}, names: 'italic' }, Botanical: { frame: function (g) { sprig(750, 50 * S, 0.7, g, 1); }, names: 'serif' }, Monogram: { frame: ringTop, names: 'serif' },
      Minimal: { frame: function () {}, names: 'serif' }, Bold: { frame: function () {}, names: 'caps' }, Noir: { frame: fHeirloom, names: 'serif' },
    };

    /* companion cards carry each design's identity (frame + name style) */
    function kit() { return KIT[st.design === 'Noir' ? 'Noir' : st.design] || KIT.Heirloom; }
    function stdCard(g) {
      bg(); kit().frame(g); var d = D(); ct('THE WEDDING OF', 96, '500', 'DM Sans', 8.5, g, 3.2); sn(168, 32, g, null, null, kit().names); drule(198, g);
      ct('Save the Date', 262, 'italic', 'Fraunces', 22, INK); ct(UP(d.date), 320, '500', 'DM Sans', 12, INK, 3); ct(UP(d.loc), 344, '400', 'DM Sans', 8, MUT, 2.4);
      ct('Formal invitation to follow', 430, 'italic', 'Fraunces', 10, MUT); ct(d.web, 460, '400', 'DM Sans', 7.5, g, 1.5);
    }
    function detCard(g) {
      bg(); kit().frame(g); var d = D(); ct('THE DETAILS', 96, '500', 'DM Sans', 8.5, g, 3.2); ct(d.p1 + ' & ' + d.p2, 138, '600', 'Fraunces', 17, INK); drule(160, g);
      var rows = [['CEREMONY', "Four o'clock · " + d.venue], ['RECEPTION', "Six o'clock · dinner & dancing"], ['ATTIRE', 'Garden formal'], ['RSVP', 'by ' + d.rsvpBy], ['ONLINE', d.web]];
      var t = 212; rows.forEach(function (r) { ct(r[0], t, '500', 'DM Sans', 8, g, 2.6); ct(r[1], t + 18, '400', 'Fraunces', 11, INK); t += 56; });
    }
    function rsvpCard(g) {
      bg(); kit().frame(g); var d = D(); ct('KINDLY REPLY', 96, '500', 'DM Sans', 8.5, g, 3.2); ct('by ' + d.rsvpBy, 144, 'italic', 'Fraunces', 18, INK); drule(170, g);
      var ins = (22.3 + 18) * S; ctx.textAlign = 'left';
      function fld(top, label) { var y = top * S; ctx.font = '400 ' + (8.5 * S) + "px 'DM Sans'"; ctx.fillStyle = MUT; ctx.fillText(label, ins, y); var lw = ctx.measureText(label).width; ctx.strokeStyle = g; ctx.lineWidth = 0.5 * S; ctx.beginPath(); ctx.moveTo(ins + lw + 8 * S, y); ctx.lineTo(1500 - ins, y); ctx.stroke(); }
      fld(216, 'Name(s)'); ctx.strokeStyle = g; ctx.lineWidth = 0.7 * S;
      ctx.beginPath(); ctx.arc(ins + 5 * S, (216 + 40) * S - 3, 4.5 * S, 0, 7); ctx.stroke(); ctx.fillStyle = INK; ctx.font = '400 ' + (9.5 * S) + "px 'DM Sans'"; ctx.fillText('accepts with pleasure', ins + 16 * S, (216 + 40) * S);
      ctx.beginPath(); ctx.arc(ins + 5 * S, (216 + 64) * S - 3, 4.5 * S, 0, 7); ctx.stroke(); ctx.fillText('declines with regret', ins + 16 * S, (216 + 64) * S);
      fld(316, 'Number attending'); ctx.textAlign = 'center'; ct('or reply online at', 436, 'italic', 'Fraunces', 9, MUT); ct(d.web, 460, '500', 'DM Sans', 9, g, 1.2);
    }
    function menuCard(g) {
      bg(); kit().frame(g); var d = D(); ct('MENU', 78, '500', 'DM Sans', 9, g, 3.4); sn(126, 15, g); drule(152, g);
      var rows = [['FIRST', d.menu1], ['MAIN', d.menu2], ['DESSERT', d.menu3]]; var t = 204;
      rows.forEach(function (r) { ct(r[0], t, '500', 'DM Sans', 8, g, 2.6); ct(r[1], t + 18, '400', 'Fraunces', 12, INK); t += 54; });
      ct('with love & gratitude', 452, 'italic', 'Fraunces', 10, MUT);
    }
    function welcomeCard(g) {
      bg(); kit().frame(g); var d = D(); ct('WELCOME', 134, '600', 'Fraunces', 44, INK);
      ct('to the wedding of', 182, 'italic', 'Fraunces', 13, MUT); sn(240, 34, g, null, null, kit().names);
      ct(UP(d.date), 302, '500', 'DM Sans', 11, INK, 3); ct(d.welcomeMsg, 360, '400', 'Fraunces', 13, INK); ct(d.loc, 386, '400', 'DM Sans', 8, MUT, 1);
    }
    function timelineCard(g) {
      bg(); kit().frame(g); ct('ORDER OF THE DAY', 78, '500', 'DM Sans', 8.5, g, 2.6); sn(126, 15, g); drule(152, g);
      var rows = [['4:00', 'Ceremony'], ['5:00', 'Cocktail hour'], ['6:30', 'Dinner is served'], ['8:00', 'First dance'], ['11:00', 'Last dance & send-off']];
      rows.forEach(function (r, i) { var y = (202 + i * 44) * S; ctx.fillStyle = g; ctx.font = '500 ' + (12 * S) + "px 'DM Sans'"; ctx.textAlign = 'right'; ctx.fillText(r[0], 0.46 * 1500, y); ctx.fillStyle = INK; ctx.font = '400 ' + (13 * S) + 'px Fraunces'; ctx.textAlign = 'left'; ctx.fillText(r[1], 0.52 * 1500, y); });
      ctx.textAlign = 'center';
    }
    function placeCard(g) {
      bg(); kit().frame(g); var d = D(); ct('PLEASE BE SEATED', 128, '500', 'DM Sans', 8, g, 3);
      ct(d.guestName, 252, '600', 'Fraunces', 30, INK); drule(288, g, 40); ct('TABLE ' + UP(d.tableNo), 326, '500', 'DM Sans', 9.5, g, 3);
    }
    function tableNumber(g) {
      bg(); kit().frame(g); var d = D(); ct('PLEASE BE SEATED', 96, '500', 'DM Sans', 8.5, g, 3); drule(118, g, 40);
      ct('TABLE', 190, '500', 'DM Sans', 11, MUT, 5); ct(d.tableNo, 272, '600', 'Fraunces', 66, INK); sn(374, 14, g); ct(UP(d.date), 400, '400', 'DM Sans', 8, MUT, 2);
    }
    function thankYouCard(g) {
      bg(); kit().frame(g); var d = D(); sn(104, 16, g, null, null, kit().names);
      ct('Thank You', 236, 'italic', 'Fraunces', 40, INK); drule(276, g, 44);
      ct('for celebrating with us', 320, 'italic', 'Fraunces', 12, MUT);
      ct(UP(d.date), 366, '400', 'DM Sans', 8.5, g, 2.4); ct(d.loc, 392, '400', 'DM Sans', 8, MUT, 1);
      ct('with love & gratitude', 452, 'italic', 'Fraunces', 10, MUT);
    }
    function vowCover(g) {
      bg(); kit().frame(g); var d = D(); ct('ON OUR WEDDING DAY', 110, '500', 'DM Sans', 7.6, g, 3);
      ct('My Vows', 232, 'italic', 'Fraunces', 44, INK); drule(272, g, 44);
      ct(d.p1 + ' to ' + d.p2, 318, '400', 'Fraunces', 13, INK);
      ct(UP(d.date), 364, '400', 'DM Sans', 8.5, MUT, 2.2); ct(d.venue, 390, '400', 'DM Sans', 8, MUT, 1);
      ct('to have & to hold', 452, 'italic', 'Fraunces', 10, MUT);
    }

    var g;
    if (st.design === 'Noir') { PAPER = '#1b1916'; INK = '#f3ead9'; MUT = '#b8ab97'; g = (st.cw === 'Custom' ? st.color : NOIRCW[st.cw] || NOIRCW.Champagne); }
    else { PAPER = '#ffffff'; INK = '#1b1916'; MUT = '#6f665b'; g = st.color || CW[st.cw] || CW.Champagne; }
    ctx.textBaseline = 'alphabetic';
    var dz = st.design === 'Noir' ? 'Heirloom' : st.design;
    switch (st.card) {
      case 'inv': (INV[dz] || invHeirloom)(g); break;
      case 'std': stdCard(g); break;
      case 'det': detCard(g); break;
      case 'rsvp': rsvpCard(g); break;
      case 'menu': menuCard(g); break;
      case 'welcome': welcomeCard(g); break;
      case 'timeline': timelineCard(g); break;
      case 'placecard': placeCard(g); break;
      case 'tableno': tableNumber(g); break;
      case 'thankyou': thankYouCard(g); break;
      case 'vowcover': vowCover(g); break;
      default: (INV[dz] || invHeirloom)(g);
    }
  }

  /* Diagonal preview watermark for un-purchased exports. On-screen previews
     stay clean; anything downloaded before purchase carries this. */
  function watermark(canvas) {
    var ctx = canvas.getContext('2d');
    ctx.save();
    ctx.globalAlpha = 0.13;
    ctx.fillStyle = '#8a651c';
    ctx.font = "500 52px 'DM Sans', sans-serif";
    ctx.textAlign = 'center';
    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.rotate(-Math.PI / 6);
    for (var y = -canvas.height; y <= canvas.height; y += 260) {
      for (var x = -canvas.width; x <= canvas.width; x += 760) {
        ctx.fillText('PREVIEW · vownook.com/shop', x, y);
      }
    }
    ctx.restore();
  }

  function loadFonts() {
    if (!document.fonts) return Promise.resolve();
    return document.fonts.ready.then(function () {
      return Promise.all([
        document.fonts.load('600 100px Fraunces'),
        document.fonts.load('italic 100px Fraunces'),
        document.fonts.load("400 40px 'DM Sans'"),
        document.fonts.load("500 40px 'DM Sans'"),
      ]);
    });
  }

  window.VNCards = { CW: CW, NOIRCW: NOIRCW, DESIGNS: DESIGNS, CARDS: CARDS, DEFAULTS: DEFAULTS, render: render, watermark: watermark, loadFonts: loadFonts };
})();
