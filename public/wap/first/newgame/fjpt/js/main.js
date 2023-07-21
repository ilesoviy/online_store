function CMain(a){var d,b=0,c=0,e=STATE_LOADING,f,g,h;this.initContainer=function(){s_oCanvas=document.getElementById("canvas");s_oStage=new createjs.Stage(s_oCanvas);createjs.Touch.enable(s_oStage);s_bMobile=jQuery.browser.mobile;!1===s_bMobile&&(s_oStage.enableMouseOver(20),$("body").on("contextmenu","#canvas",function(a){return!1}));s_iPrevTime=(new Date).getTime();createjs.Ticker.addEventListener("tick",this._update);createjs.Ticker.setFPS(30);navigator.userAgent.match(/Windows Phone/i)&&(DISABLE_SOUND_MOBILE=
!0);s_oSpriteLibrary=new CSpriteLibrary;g=new CPreloader};this.preloaderReady=function(){!1!==DISABLE_SOUND_MOBILE&&!1!==s_bMobile||this._initSounds();this._loadImages();d=!0};this.soundLoaded=function(){b++;g.refreshLoader(Math.floor(b/c*100));if(b===c){g.unload();if(!1===DISABLE_SOUND_MOBILE||!1===s_bMobile)s_oSoundtrack=createjs.Sound.play("soundtrack",{loop:-1});this.gotoMenu()}};this._initSounds=function(){createjs.Sound.initializeDefaultPlugins()&&(0<navigator.userAgent.indexOf("Opera")||0<
navigator.userAgent.indexOf("OPR")?(createjs.Sound.alternateExtensions=["mp3"],createjs.Sound.addEventListener("fileload",createjs.proxy(this.soundLoaded,this)),createjs.Sound.registerSound("./sounds/soundtrack.ogg","soundtrack"),createjs.Sound.registerSound("./sounds/press_button.ogg","click"),createjs.Sound.registerSound("./sounds/game_over.ogg","game_over"),createjs.Sound.registerSound("./sounds/swoosh.ogg","swoosh")):(createjs.Sound.alternateExtensions=["ogg"],createjs.Sound.addEventListener("fileload",
createjs.proxy(this.soundLoaded,this)),createjs.Sound.registerSound("./sounds/soundtrack.mp3","soundtrack"),createjs.Sound.registerSound("./sounds/press_button.mp3","click"),createjs.Sound.registerSound("./sounds/game_over.mp3","game_over"),createjs.Sound.registerSound("./sounds/swoosh.mp3","swoosh")),c+=3)};this._loadImages=function(){s_oSpriteLibrary.init(this._onImagesLoaded,this._onAllImagesLoaded,this);s_oSpriteLibrary.addSprite("but_play","./sprites/but_play.png");s_oSpriteLibrary.addSprite("msg_box",
"./sprites/msg_box.png");s_oSpriteLibrary.addSprite("bg_menu","./sprites/bg_menu.jpg");s_oSpriteLibrary.addSprite("bg_mode","./sprites/bg_mode.jpg");s_oSpriteLibrary.addSprite("bg_game","./sprites/bg_game.jpg");s_oSpriteLibrary.addSprite("but_exit","./sprites/but_exit.png");s_oSpriteLibrary.addSprite("audio_icon","./sprites/audio_icon.png");s_oSpriteLibrary.addSprite("layout_3x3","./sprites/layout_3x3.png");s_oSpriteLibrary.addSprite("layout_4x4","./sprites/layout_4x4.png");s_oSpriteLibrary.addSprite("layout_5x5",
"./sprites/layout_5x5.png");s_oSpriteLibrary.addSprite("image_1","./sprites/image_1.jpg");s_oSpriteLibrary.addSprite("image_2","./sprites/image_2.jpg");s_oSpriteLibrary.addSprite("image_3","./sprites/image_3.jpg");s_oSpriteLibrary.addSprite("logo","./sprites/logo_overlay.png");s_oSpriteLibrary.addSprite("time_display","./sprites/time_display.png");s_oSpriteLibrary.addSprite("but_timer","./sprites/but_timer.png");s_oSpriteLibrary.addSprite("but_restart","./sprites/but_restart.png");s_oSpriteLibrary.addSprite("but_preview",
"./sprites/but_preview.png");c+=s_oSpriteLibrary.getNumSprites();s_oSpriteLibrary.loadSprites()};this._onImagesLoaded=function(){b++;g.refreshLoader(Math.floor(b/c*100));if(b===c){g.unload();if(!1===DISABLE_SOUND_MOBILE||!1===s_bMobile)s_oSoundtrack=createjs.Sound.play("soundtrack",{loop:-1});this.gotoMenu()}};this._onAllImagesLoaded=function(){};this.onAllPreloaderImagesLoaded=function(){this._loadImages()};this.gotoMenu=function(){new CMenu;e=STATE_MENU};this.gotoModeMenu=function(){new CModeMenu;
e=STATE_MENU};this.gotoGame=function(a,b,c){s_iMode=a;s_szImage=b;s_bNumActive=c;h=new CGame(f);e=STATE_GAME;$(s_oMain).trigger("game_start")};this.gotoHelp=function(){new CHelp;e=STATE_HELP};this.stopUpdate=function(){d=!1};this.startUpdate=function(){d=!0};this._update=function(a){if(!1!==d){var b=(new Date).getTime();s_iTimeElaps=b-s_iPrevTime;s_iCntTime+=s_iTimeElaps;s_iCntFps++;s_iPrevTime=b;1E3<=s_iCntTime&&(s_iCurFps=s_iCntFps,s_iCntTime-=1E3,s_iCntFps=0);e===STATE_GAME&&h.update();s_oStage.update(a)}};
s_oMain=this;f=a;this.initContainer()}var s_bMobile,s_bAudioActive=!0,s_iCntTime=0,s_iTimeElaps=0,s_iPrevTime=0,s_iCntFps=0,s_iCurFps=0,s_iMode,s_szImage,s_bNumActive,s_oDrawLayer,s_oStage,s_oMain,s_oSpriteLibrary,s_oSoundTrack,s_oCanvas;TEXT_GAMEOVER="YOU WIN";TEXT_SCORE="TIME ELAPSED: ";TEXT_PAUSE="PAUSE";TEXT_DIFFICULTY="1) CHOOSE DIFFICULTY";TEXT_IMAGE="2) CHOOSE IMAGE";
function CInterface(){var a,d,b,c,e,f,g,h,l,k,n,p,t=null,r;this._init=function(){var m=s_oSpriteLibrary.getSprite("but_exit");b=CANVAS_WIDTH-m.height/2-10;c=m.height/2+10;h=new CGfxButton(b,c,m,s_oStage);h.addEventListener(ON_MOUSE_UP,this._onExit,this);a=CANVAS_WIDTH-m.width/2-120;d=m.height/2+10;if(!1===DISABLE_SOUND_MOBILE||!1===s_bMobile)m=s_oSpriteLibrary.getSprite("audio_icon"),f=new CToggle(a,d,m,s_bAudioActive),f.addEventListener(ON_MOUSE_UP,this._onAudioToggle,this);m=s_oSpriteLibrary.getSprite("time_display");
n=createBitmap(m);n.x=1570;n.y=220;s_oStage.addChild(n);p=new createjs.Text("00:00","bold 60px Arial","#ffffff");p.x=1595;p.y=285;p.textAlign="left";p.textBaseline="alphabetic";p.lineWidth=200;s_oStage.addChild(p);m=s_oSpriteLibrary.getSprite("but_timer");g=new CGfxButton(1670,420,m,s_oStage);g.addEventListener(ON_MOUSE_UP,this._onTimer,this);m=s_oSpriteLibrary.getSprite("but_restart");l=new CGfxButton(1670,620,m,s_oStage);l.addEventListener(ON_MOUSE_UP,this._onRestart,this);e=!1;m=s_oSpriteLibrary.getSprite("but_preview");
k=new CToggle(1670,820,m,s_oStage);k.addEventListener(ON_MOUSE_DOWN,this._onPreview,this);this.setButVisible(!1);this.refreshButtonPos(s_iOffsetX,s_iOffsetY)};this.unload=function(){if(!1===DISABLE_SOUND_MOBILE||!1===s_bMobile)f.unload(),f=null;g.unload();h.unload();k.unload();l.unload();null!==t&&t.unload();s_oInterface=null};this.refreshButtonPos=function(e,g){h.setPosition(b-e,g+c);!1!==DISABLE_SOUND_MOBILE&&!1!==s_bMobile||f.setPosition(a-e,g+d)};this.setButVisible=function(a){g.setVisible(a);
l.setVisible(a);k.setVisible(a)};this.refreshTime=function(a){p.text=a};this._onTimer=function(){s_oGame.onPause()};this._onRestart=function(){s_oGame.restartGame()};this._onPreview=function(){(e=!e)?r=new CPreviewPanel:r.unload()};this._onButHelpRelease=function(){t=new CHelpPanel};this._onButRestartRelease=function(){s_oGame.restartGame()};this.onExitFromHelp=function(){t.unload()};this._onAudioToggle=function(){createjs.Sound.setMute(s_bAudioActive);s_bAudioActive=!s_bAudioActive};this._onExit=
function(){s_oGame.onExit()};s_oInterface=this;this._init();return this}var s_oInterface=null;
function CGfxButton(a,d,b){var c,e,f,g=[],h;this._init=function(a,b,d){c=1;e=[];f=[];h=createBitmap(d);h.x=a;h.y=b;h.regX=d.width/2;h.regY=d.height/2;s_oStage.addChild(h);this._initListener()};this.unload=function(){h.off("mousedown",this.buttonDown);h.off("pressup",this.buttonRelease);s_oStage.removeChild(h)};this.setVisible=function(a){h.visible=a};this._initListener=function(){h.on("mousedown",this.buttonDown);h.on("pressup",this.buttonRelease)};this.addEventListener=function(a,b,c){e[a]=b;f[a]=
c};this.addEventListenerWithParams=function(a,b,c,d){e[a]=b;f[a]=c;g=d};this.buttonRelease=function(){h.scaleX=c;h.scaleY=c;e[ON_MOUSE_UP]&&e[ON_MOUSE_UP].call(f[ON_MOUSE_UP],g)};this.buttonDown=function(){h.scaleX=.9*c;h.scaleY=.9*c;e[ON_MOUSE_DOWN]&&e[ON_MOUSE_DOWN].call(f[ON_MOUSE_DOWN],g)};this.setScale=function(a){c=a;h.scaleX=a;h.scaleY=a};this.setPosition=function(a,b){h.x=a;h.y=b};this.setX=function(a){h.x=a};this.setY=function(a){h.y=a};this.getButtonImage=function(){return h};this.getX=
function(){return h.x};this.getY=function(){return h.y};this._init(a,d,b);return this}
function CGame(a){var d,b,c,e,f,g,h,l,k,n,p,t,r,m,w,u=null,v,q,x,y,z;this._init=function(){d=!1;t=NUM_SHUFFLE[s_iMode];k=s_iMode+3;n=k*k;r=0;m=null;var a=createBitmap(s_oSpriteLibrary.getSprite("bg_game"));s_oStage.addChild(a);f=[];for(a=0;a<k;a++){f[a]=[];for(var b=0;b<k;b++)f[a][b]=null}z=s_oSpriteLibrary.getSprite(s_szImage);h=z.width;l=z.height;e=[];for(var c=h/k,g=l/k,p=c/2,u=g/2,a=0;a<k;a++)for(e[a]=[],b=0;b<k;b++)e[a][b]={x:p+b*(c+3),y:u+a*(g+3)};a=3*(k-1);q=new createjs.Container;q.x=CANVAS_WIDTH/
2-110;q.y=CANVAS_HEIGHT/2;q.regX=h/2+a/2;q.regY=l/2+a/2;s_oStage.addChild(q);this._initPieces();b=s_oSpriteLibrary.getSprite("logo");y=createBitmap(b);y.x=250;y.y=150;y.regX=b.width/2;y.regY=b.height/2;s_oStage.addChild(y);w=new CInterface;x=new createjs.Shape;x.graphics.beginFill("rgba(158,158,158,0.01)").drawRect(0,0,h+a,l+a);x.on("click",function(){});q.addChild(x);this._shufflePieces();a=new Hammer(s_oCanvas);a.get("swipe").set({direction:Hammer.DIRECTION_ALL});a.get("swipe").set({velocity:.005});
a.get("swipe").set({threshold:.1});a.on("swipeleft",function(){v._swipeControl("left")});a.on("swiperight",function(){v._swipeControl("right")});a.on("swipeup",function(){v._swipeControl("up")});a.on("swipedown",function(){v._swipeControl("down")})};this._swipeControl=function(a){if(null!==m){var b=c[m].getLogicPos().row,d=c[m].getLogicPos().col,e;switch(a){case "left":if(0===d)return;e=f[b][d-1];break;case "right":if(d===k-1)return;e=f[b][d+1];break;case "up":if(0===b)return;e=f[b-1][d];break;case "down":if(b===
k-1)return;e=f[b+1][d]}c[m].setTargetVisible(!1);v._movePieces(m,e);m=null}};this._initPieces=function(){var a,d,g=0,h=0;c=[];for(var m=0;m<n;m++)a=e[g][h].x,d=e[g][h].y,c[m]=new CPiece(a,d,m,q,g,h),c[m].getIndex(),f[g][h]=m,h++,h===k&&(h=0,g++);b=!0};this._shufflePieces=function(){t--;if(0>t)w.setButVisible(!0),b=!1,x.visible=!1,d=!0;else{var a=Math.floor(Math.random()*n),e=c[a].getLogicPos().row,f=c[a].getLogicPos().col;this._updateNeighborList(e,f);e=g[Math.floor(Math.random()*g.length)];this._movePieces(a,
e)}};this._movePieces=function(a,b){x.visible=!0;!1!==DISABLE_SOUND_MOBILE&&!1!==s_bMobile||createjs.Sound.play("swoosh");var d=c[a].getPos().x,e=c[a].getPos().y,g=c[a].getLogicPos().row,f=c[a].getLogicPos().col,h=c[b].getPos().x,k=c[b].getPos().y,m=c[b].getLogicPos().row,l=c[b].getLogicPos().col;p=2;c[a].move(h,k);c[a].setLogicPos(m,l);c[b].move(d,e);c[b].setLogicPos(g,f);this._updateLogicGrid()};this._updateNeighborList=function(a,b){g=[];0<=a-1&&g.push(f[a-1][b]);a+1<k&&g.push(f[a+1][b]);0<=b-
1&&g.push(f[a][b-1]);b+1<k&&g.push(f[a][b+1])};this._isInNeighbor=function(a){for(var b=!1,c=0;c<g.length;c++)if(g[c]===a){b=!0;break}return b};this._updateLogicGrid=function(){for(var a,b,d=0;d<n;d++)a=c[d].getLogicPos().row,b=c[d].getLogicPos().col,f[a][b]=c[d].getIndex()};this.onCellMoved=function(){p--;0===p&&b?this._shufflePieces():0!==p||b||(this._updateLogicGrid(),this._checkWin(),x.visible=!1)};this.onPieceClick=function(a){if(null===m)m=a,c[m].setTargetVisible(!0);else{if(null!==m&&this._isInNeighbor(a)){this._movePieces(m,
a);c[m].setTargetVisible(!1);m=null;return}m===a||this._isInNeighbor(a)||(c[m].setTargetVisible(!1),m=a,c[a].setTargetVisible(!0))}this._updateNeighborList(c[m].getLogicPos().row,c[m].getLogicPos().col)};this._checkWin=function(){for(var a=0,b=0;b<k;b++)for(var c=0;c<k;c++){if(f[b][c]!==a)return;a++}d=!1;this.gameOver()};this.restartGame=function(){this.unload();this._init()};this.unload=function(){d=!1;x.off("click",function(){});w.unload();null!==u&&u.unload();for(var a=0;a<c.length;a++)c[a].unload();
createjs.Tween.removeAllTweens();s_oStage.removeAllChildren()};this.onPause=function(){d=!1;new CPausePanel(s_oSpriteLibrary.getSprite("msg_box"))};this.onPauseExit=function(){d=!0};this.onExit=function(){this.unload();s_oMain.gotoMenu();$(s_oMain).trigger("restart")};this._onExitHelp=function(){d=!0};this.gameOver=function(){u=CEndPanel(s_oSpriteLibrary.getSprite("msg_box"));u.show(r)};this.update=function(){d&&(r+=s_iTimeElaps,5999E3<r&&(r=5999E3),w.refreshTime(formatTime(r)))};s_oGame=this;NUM_SHUFFLE[0]=
a.num_shuffle_3x3;NUM_SHUFFLE[1]=a.num_shuffle_4x4;NUM_SHUFFLE[2]=a.num_shuffle_5x5;SHUFFLE_SPEED[0]=a.shuffle_speed_3x3;SHUFFLE_SPEED[1]=a.shuffle_speed_4x4;SHUFFLE_SPEED[2]=a.shuffle_speed_5x5;v=this;this._init()}var s_oGame;
function CEndPanel(a){var d,b,c,e;this._init=function(a){d=createBitmap(a);d.x=0;d.y=0;c=new createjs.Text(""," 100px blackplotan","#008df0");c.x=CANVAS_WIDTH/2;c.y=CANVAS_HEIGHT/2-162;c.textAlign="center";c.textBaseline="alphabetic";c.lineWidth=500;e=new createjs.Text(""," 80px blackplotan","#008df0");e.x=CANVAS_WIDTH/2;e.y=CANVAS_HEIGHT/2+52;e.textAlign="center";e.textBaseline="alphabetic";e.lineWidth=500;b=new createjs.Container;b.alpha=0;b.visible=!1;b.addChild(d,e,c);s_oStage.addChild(b)};this.unload=
function(){b.off("mousedown",this._onExit)};this._initListener=function(){b.on("mousedown",this._onExit)};this.show=function(a){!1!==DISABLE_SOUND_MOBILE&&!1!==s_bMobile||createjs.Sound.play("game_over");c.text=TEXT_GAMEOVER;e.text=TEXT_SCORE+formatTime(a);b.visible=!0;var d=this;createjs.Tween.get(b).to({alpha:1},500).call(function(){d._initListener()});$(s_oMain).trigger("save_score",[a,s_bNumActive])};this._onExit=function(){b.off("mousedown",this._onExit);s_oStage.removeChild(b);s_oGame.onExit()};
this._init(a);return this}
function CSpriteLibrary(){var a,d,b,c,e,f;this.init=function(g,h,l){b=d=0;c=g;e=h;f=l;a={}};this.addSprite=function(b,c){a.hasOwnProperty(b)||(a[b]={szPath:c,oSprite:new Image},d++)};this.getSprite=function(b){return a.hasOwnProperty(b)?a[b].oSprite:null};this._onSpritesLoaded=function(){e.call(f)};this._onSpriteLoaded=function(){c.call(f);++b==d&&this._onSpritesLoaded()};this.loadSprites=function(){for(var b in a)a[b].oSprite.oSpriteLibrary=this,a[b].oSprite.onload=function(){this.oSpriteLibrary._onSpriteLoaded()},a[b].oSprite.src=
a[b].szPath};this.getNumSprites=function(){return d}}var CANVAS_WIDTH=1920,CANVAS_HEIGHT=1080,EDGEBOARD_X=140,EDGEBOARD_Y=70,FPS_TIME=1E3/24,DISABLE_SOUND_MOBILE=!1,STATE_LOADING=0,STATE_MENU=1,STATE_HELP=1,STATE_GAME=3,ON_MOUSE_DOWN=0,ON_MOUSE_UP=1,ON_MOUSE_OVER=2,ON_MOUSE_OUT=3,ON_DRAG_START=4,ON_DRAG_END=5,EASY_MODE=0,NORMAL_MODE=1,HARD_MODE=2,NUM_SHUFFLE=[],SHUFFLE_SPEED=[];
function CToggle(a,d,b,c){var e,f,g,h=[],l;this._init=function(a,b,c,d){f=[];g=[];var h=new createjs.SpriteSheet({images:[c],frames:{width:c.width/2,height:c.height,regX:c.width/2/2,regY:c.height/2},animations:{state_true:[0],state_false:[1]}});e=d;l=createSprite(h,"state_"+e,c.width/2/2,c.height/2,c.width/2,c.height);l.x=a;l.y=b;l.stop();s_oStage.addChild(l);this._initListener()};this.unload=function(){l.off("mousedown",this.buttonDown);l.off("pressup",this.buttonRelease);s_oStage.removeChild(l)};
this._initListener=function(){l.on("mousedown",this.buttonDown);l.on("pressup",this.buttonRelease)};this.addEventListener=function(a,b,c){f[a]=b;g[a]=c};this.addEventListenerWithParams=function(a,b,c,d){f[a]=b;g[a]=c;h=d};this.setActive=function(a){e=a;l.gotoAndStop("state_"+e)};this.buttonRelease=function(){l.scaleX=1;l.scaleY=1;!1!==DISABLE_SOUND_MOBILE&&!1!==s_bMobile||createjs.Sound.play("click");e=!e;l.gotoAndStop("state_"+e);f[ON_MOUSE_UP]&&f[ON_MOUSE_UP].call(g[ON_MOUSE_UP],h)};this.buttonDown=
function(){l.scaleX=.9;l.scaleY=.9;f[ON_MOUSE_DOWN]&&f[ON_MOUSE_DOWN].call(g[ON_MOUSE_DOWN],h)};this.setPosition=function(a,b){l.x=a;l.y=b};this.setVisible=function(a){l.visible=a};this._init(a,d,b,c)}
(function(a){(jQuery.browser=jQuery.browser||{}).mobile=/android|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(ad|hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|tablet|treo|up\.(browser|link)|vodafone|wap|webos|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i.test(a.substr(0,
4))})(navigator.userAgent||navigator.vendor||window.opera);$(window).resize(function(){sizeHandler()});function trace(a){console.log(a)}
function getSize(a){var d=a.toLowerCase(),b=window.document,c=b.documentElement;if(void 0===window["inner"+a])a=c["client"+a];else if(window["inner"+a]!=c["client"+a]){var e=b.createElement("body");e.id="vpw-test-b";e.style.cssText="overflow:scroll";var f=b.createElement("div");f.id="vpw-test-d";f.style.cssText="position:absolute;top:-1000px";f.innerHTML="<style>@media("+d+":"+c["client"+a]+"px){body#vpw-test-b div#vpw-test-d{"+d+":7px!important}}</style>";e.appendChild(f);c.insertBefore(e,b.head);
a=7==f["offset"+a]?c["client"+a]:window["inner"+a];c.removeChild(e)}else a=window["inner"+a];return a}$(window).ready(function(){sizeHandler()});window.addEventListener("orientationchange",onOrientationChange);function onOrientationChange(){window.matchMedia("(orientation: portrait)").matches&&sizeHandler();window.matchMedia("(orientation: landscape)").matches&&sizeHandler()}function getIOSWindowHeight(){return document.documentElement.clientWidth/window.innerWidth*window.innerHeight}
function getHeightOfIOSToolbars(){var a=(0===window.orientation?screen.height:screen.width)-getIOSWindowHeight();return 1<a?a:0}
function sizeHandler(){window.scrollTo(0,1);if($("#canvas")){var a;a=navigator.userAgent.match(/(iPad|iPhone|iPod)/g)?getIOSWindowHeight():getSize("Height");var d=getSize("Width"),b=Math.min(a/CANVAS_HEIGHT,d/CANVAS_WIDTH),c=CANVAS_WIDTH*b,b=CANVAS_HEIGHT*b,e=0;b<a?(e=a-b,b+=e,c+=CANVAS_WIDTH/CANVAS_HEIGHT*e):c<d&&(e=d-c,c+=e,b+=CANVAS_HEIGHT/CANVAS_WIDTH*e);var e=a/2-b/2,f=d/2-c/2,g=CANVAS_WIDTH/c;if(f*g<-EDGEBOARD_X||e*g<-EDGEBOARD_Y)b=Math.min(a/(CANVAS_HEIGHT-2*EDGEBOARD_Y),d/(CANVAS_WIDTH-2*
EDGEBOARD_X)),c=CANVAS_WIDTH*b,b*=CANVAS_HEIGHT,e=(a-b)/2,f=(d-c)/2,g=CANVAS_WIDTH/c;s_iOffsetX=-1*f*g;s_iOffsetY=-1*e*g;0<=e&&(s_iOffsetY=0);0<=f&&(s_iOffsetX=0);null!==s_oInterface&&s_oInterface.refreshButtonPos(s_iOffsetX,s_iOffsetY);null!==s_oMenu&&s_oMenu.refreshButtonPos(s_iOffsetX,s_iOffsetY);null!==s_oModeMenu&&s_oModeMenu.refreshButtonPos(s_iOffsetX,s_iOffsetY);$("#canvas").css("width",c+"px");$("#canvas").css("height",b+"px");0>e?$("#canvas").css("top",e+"px"):$("#canvas").css("top","0px");
$("#canvas").css("left",f+"px")}}function createBitmap(a,d,b){var c=new createjs.Bitmap(a),e=new createjs.Shape;d&&b?e.graphics.beginFill("#fff").drawRect(0,0,d,b):e.graphics.beginFill("#ff0").drawRect(0,0,a.width,a.height);c.hitArea=e;return c}function createSprite(a,d,b,c,e,f){a=null!==d?new createjs.Sprite(a,d):new createjs.Sprite(a);d=new createjs.Shape;d.graphics.beginFill("#000000").drawRect(-b,-c,e,f);a.hitArea=d;return a}
function randomFloatBetween(a,d,b){"undefined"===typeof b&&(b=2);return parseFloat(Math.min(a+Math.random()*(d-a),d).toFixed(b))}function rotateVector2D(a,d){var b=d.getX()*Math.cos(a)+d.getY()*Math.sin(a),c=d.getX()*-Math.sin(a)+d.getY()*Math.cos(a);d.set(b,c)}function tweenVectorsOnX(a,d,b){return a+b*(d-a)}function shuffle(a){for(var d=a.length,b,c;0!==d;)c=Math.floor(Math.random()*d),--d,b=a[d],a[d]=a[c],a[c]=b;return a}
function bubbleSort(a){var d;do{d=!1;for(var b=0;b<a.length-1;b++)a[b]>a[b+1]&&(d=a[b],a[b]=a[b+1],a[b+1]=d,d=!0)}while(d)}function compare(a,d){return a.index>d.index?-1:a.index<d.index?1:0}function easeLinear(a,d,b,c){return b*a/c+d}function easeInQuad(a,d,b,c){return b*(a/=c)*a+d}function easeInSine(a,d,b,c){return-b*Math.cos(a/c*(Math.PI/2))+b+d}function easeInCubic(a,d,b,c){return b*(a/=c)*a*a+d}
function getTrajectoryPoint(a,d){var b=new createjs.Point,c=(1-a)*(1-a),e=a*a;b.x=c*d.start.x+2*(1-a)*a*d.traj.x+e*d.end.x;b.y=c*d.start.y+2*(1-a)*a*d.traj.y+e*d.end.y;return b}function formatTime(a){a/=1E3;var d=Math.floor(a/60);a=Math.floor(a-60*d);var b="",b=10>d?b+("0"+d+":"):b+(d+":");return 10>a?b+("0"+a):b+a}function degreesToRadians(a){return a*Math.PI/180}function checkRectCollision(a,d){var b,c;b=getBounds(a,.9);c=getBounds(d,.98);return calculateIntersection(b,c)}
function calculateIntersection(a,d){var b,c,e,f,g,h,l,k;b=a.x+(e=a.width/2);c=a.y+(f=a.height/2);g=d.x+(h=d.width/2);l=d.y+(k=d.height/2);b=Math.abs(b-g)-(e+h);c=Math.abs(c-l)-(f+k);return 0>b&&0>c?(b=Math.min(Math.min(a.width,d.width),-b),c=Math.min(Math.min(a.height,d.height),-c),{x:Math.max(a.x,d.x),y:Math.max(a.y,d.y),width:b,height:c,rect1:a,rect2:d}):null}
function getBounds(a,d){var b={x:Infinity,y:Infinity,width:0,height:0};if(a instanceof createjs.Container){b.x2=-Infinity;b.y2=-Infinity;var c=a.children,e=c.length,f,g;for(g=0;g<e;g++)f=getBounds(c[g],1),f.x<b.x&&(b.x=f.x),f.y<b.y&&(b.y=f.y),f.x+f.width>b.x2&&(b.x2=f.x+f.width),f.y+f.height>b.y2&&(b.y2=f.y+f.height);Infinity==b.x&&(b.x=0);Infinity==b.y&&(b.y=0);Infinity==b.x2&&(b.x2=0);Infinity==b.y2&&(b.y2=0);b.width=b.x2-b.x;b.height=b.y2-b.y;delete b.x2;delete b.y2}else{var h,l;a instanceof createjs.Bitmap?
(e=a.sourceRect||a.image,g=e.width*d,h=e.height*d):a instanceof createjs.Sprite?a.spriteSheet._frames&&a.spriteSheet._frames[a.currentFrame]&&a.spriteSheet._frames[a.currentFrame].image?(e=a.spriteSheet.getFrame(a.currentFrame),g=e.rect.width,h=e.rect.height,c=e.regX,l=e.regY):(b.x=a.x||0,b.y=a.y||0):(b.x=a.x||0,b.y=a.y||0);c=c||0;g=g||0;l=l||0;h=h||0;b.regX=c;b.regY=l;e=a.localToGlobal(0-c,0-l);f=a.localToGlobal(g-c,h-l);g=a.localToGlobal(g-c,0-l);c=a.localToGlobal(0-c,h-l);b.x=Math.min(Math.min(Math.min(e.x,
f.x),g.x),c.x);b.y=Math.min(Math.min(Math.min(e.y,f.y),g.y),c.y);b.width=Math.max(Math.max(Math.max(e.x,f.x),g.x),c.x)-b.x;b.height=Math.max(Math.max(Math.max(e.y,f.y),g.y),c.y)-b.y}return b}function NoClickDelay(a){this.element=a;window.Touch&&this.element.addEventListener("touchstart",this,!1)}function shuffle(a){for(var d=a.length,b,c;0<d;)c=Math.floor(Math.random()*d),d--,b=a[d],a[d]=a[c],a[c]=b;return a}
NoClickDelay.prototype={handleEvent:function(a){switch(a.type){case "touchstart":this.onTouchStart(a);break;case "touchmove":this.onTouchMove(a);break;case "touchend":this.onTouchEnd(a)}},onTouchStart:function(a){a.preventDefault();this.moved=!1;this.element.addEventListener("touchmove",this,!1);this.element.addEventListener("touchend",this,!1)},onTouchMove:function(a){this.moved=!0},onTouchEnd:function(a){this.element.removeEventListener("touchmove",this,!1);this.element.removeEventListener("touchend",
this,!1);if(!this.moved){a=document.elementFromPoint(a.changedTouches[0].clientX,a.changedTouches[0].clientY);3==a.nodeType&&(a=a.parentNode);var d=document.createEvent("MouseEvents");d.initEvent("click",!0,!0);a.dispatchEvent(d)}}};
(function(){function a(a){var c={focus:"visible",focusin:"visible",pageshow:"visible",blur:"hidden",focusout:"hidden",pagehide:"hidden"};a=a||window.event;a.type in c?document.body.className=c[a.type]:(document.body.className=this[d]?"hidden":"visible","hidden"===document.body.className?s_oMain.stopUpdate():s_oMain.startUpdate())}var d="hidden";d in document?document.addEventListener("visibilitychange",a):(d="mozHidden")in document?document.addEventListener("mozvisibilitychange",a):(d="webkitHidden")in
document?document.addEventListener("webkitvisibilitychange",a):(d="msHidden")in document?document.addEventListener("msvisibilitychange",a):"onfocusin"in document?document.onfocusin=document.onfocusout=a:window.onpageshow=window.onpagehide=window.onfocus=window.onblur=a})();
function CTextButton(a,d,b,c,e,f,g){var h,l,k,n,p;this._init=function(a,b,c,d,e,f,g){h=[];l=[];var x=createBitmap(c),y=Math.ceil(g/20);p=new createjs.Text(d,"bold "+g+"px "+e,"#000000");p.textAlign="center";p.textBaseline="alphabetic";var z=p.getBounds();p.x=c.width/2+y;p.y=Math.floor(c.height/2)+z.height/3+y;n=new createjs.Text(d,"bold "+g+"px "+e,f);n.textAlign="center";n.textBaseline="alphabetic";z=n.getBounds();n.x=c.width/2;n.y=Math.floor(c.height/2)+z.height/3;k=new createjs.Container;k.x=a;
k.y=b;k.regX=c.width/2;k.regY=c.height/2;k.addChild(x,p,n);s_oStage.addChild(k);this._initListener()};this.unload=function(){k.off("mousedown");k.off("pressup");s_oStage.removeChild(k)};this.setVisible=function(a){k.visible=a};this._initListener=function(){oParent=this;k.on("mousedown",this.buttonDown);k.on("pressup",this.buttonRelease)};this.addEventListener=function(a,b,c){h[a]=b;l[a]=c};this.buttonRelease=function(){k.scaleX=1;k.scaleY=1;h[ON_MOUSE_UP]&&h[ON_MOUSE_UP].call(l[ON_MOUSE_UP])};this.buttonDown=
function(){k.scaleX=.9;k.scaleY=.9;h[ON_MOUSE_DOWN]&&h[ON_MOUSE_DOWN].call(l[ON_MOUSE_DOWN])};this.setTextPosition=function(a){n.y=a;p.y=a+2};this.setPosition=function(a,b){k.x=a;k.y=b};this.setX=function(a){k.x=a};this.setY=function(a){k.y=a};this.getButtonImage=function(){return k};this.getX=function(){return k.x};this.getY=function(){return k.y};this._init(a,d,b,c,e,f,g);return this}
function CPreviewPanel(){var a,d,b,c;this._init=function(){b=s_oSpriteLibrary.getSprite(s_szImage);c=createBitmap(b);a=b.width;d=b.height;c.x=CANVAS_WIDTH/2-110;c.y=CANVAS_HEIGHT/2;c.regX=a/2;c.regY=d/2;c.scaleX=.1;c.scaleY=.1;s_oStage.addChild(c);createjs.Tween.get(c).to({scaleX:1.1,scaleY:1.1},1500,createjs.Ease.elasticOut)};this.unload=function(){createjs.Tween.removeTweens(c);s_oStage.removeChild(c)};this._init()}
function CPreloader(){var a,d,b,c,e,f,g;this._init=function(){s_oSpriteLibrary.init(this._onImagesLoaded,this._onAllImagesLoaded,this);s_oSpriteLibrary.addSprite("bg_menu","./sprites/bg_menu.jpg");s_oSpriteLibrary.addSprite("progress_bar","./sprites/progress_bar.png");s_oSpriteLibrary.loadSprites();g=new createjs.Container;s_oStage.addChild(g)};this.unload=function(){g.removeAllChildren()};this.hide=function(){var a=this;setTimeout(function(){createjs.Tween.get(f).to({alpha:1},500).call(function(){a.unload();
s_oMain.gotoMenu()})},1E3)};this._onImagesLoaded=function(){};this._onAllImagesLoaded=function(){this.attachSprites();s_oMain.preloaderReady()};this.attachSprites=function(){var h=createBitmap(s_oSpriteLibrary.getSprite("bg_menu"));g.addChild(h);h=s_oSpriteLibrary.getSprite("progress_bar");c=createBitmap(h);c.x=CANVAS_WIDTH/2-h.width/2;c.y=CANVAS_HEIGHT-250;g.addChild(c);a=h.width;d=h.height;e=new createjs.Shape;e.graphics.beginFill("rgba(255,255,255,0.01)").drawRect(c.x,c.y,1,d);g.addChild(e);c.mask=
e;b=new createjs.Text("","30px Arial","#fff");b.x=CANVAS_WIDTH/2;b.y=CANVAS_HEIGHT-250;b.shadow=new createjs.Shadow("#000",2,2,2);b.textBaseline="alphabetic";b.textAlign="center";g.addChild(b);f=new createjs.Shape;f.graphics.beginFill("black").drawRect(0,0,CANVAS_WIDTH,CANVAS_HEIGHT);f.alpha=0;g.addChild(f)};this.refreshLoader=function(f){b.text=f+"%";e.graphics.clear();f=Math.floor(f*a/100);e.graphics.beginFill("rgba(255,255,255,0.01)").drawRect(c.x,c.y,f,d)};this._init()}
function CPiece(a,d,b,c,e,f){var g,h,l,k,n,p,t,r,m,w,u;this._init=function(a,b,c,d,e,f){p=e;t=f;h=s_iMode+3;g=h*h;n=c;m=new createjs.Container;m.x=a;m.y=b;m.on("mousedown",this._onPieceClick,this,!1,n);d.addChild(m);r=s_oSpriteLibrary.getSprite(s_szImage);l=r.width;k=r.height;a=l/h;b=k/h;c=new createjs.SpriteSheet({images:[r],frames:{width:a,height:b,regX:a/2,regY:b/2},animations:{image:[0,g-1]}});w=createSprite(c,"image",a/2,b/2,a,b);w.gotoAndStop(n);u=new createjs.Shape;u.graphics.beginStroke("#ff8814").setStrokeStyle(10).drawRect(-a/
2,-b/2,a,b);u.visible=!1;m.addChild(w,u)};this.unload=function(){m.off("mousedown",this._onPieceClick,this,!1,n);c.removeChild(m)};this._onPieceClick=function(a,b){s_oGame.onPieceClick(b)};this.getIndex=function(){return n};this.setTargetVisible=function(a){u.visible=a};this.setInvisible=function(){m.visible=!1;n=-1};this.move=function(a,b){createjs.Tween.get(m).to({x:a,y:b},SHUFFLE_SPEED[s_iMode],createjs.Ease.cubicOut).call(function(){s_oGame.onCellMoved()})};this.getPos=function(){return{x:m.x,
y:m.y}};this.setLogicPos=function(a,b){p=a;t=b};this.getLogicPos=function(){return{row:p,col:t}};this._init(a,d,b,c,e,f)}
function CPausePanel(a){var d,b,c;this._init=function(a){d=createBitmap(a);d.x=0;d.y=0;c=new createjs.Text(""," 100px blackplotan","#008df0");c.x=CANVAS_WIDTH/2;c.y=CANVAS_HEIGHT/2;c.textAlign="center";c.textBaseline="alphabetic";c.lineWidth=500;b=new createjs.Container;b.alpha=0;b.visible=!1;b.addChild(d,c);s_oStage.addChild(b);this.show()};this.unload=function(){b.off("mousedown",this._onExit)};this._initListener=function(){b.on("mousedown",this._onExit)};this.show=function(){!1!==DISABLE_SOUND_MOBILE&&
!1!==s_bMobile||createjs.Sound.play("game_over");c.text=TEXT_PAUSE;b.visible=!0;var a=this;createjs.Tween.get(b).to({alpha:1},500).call(function(){a._initListener()})};this._onExit=function(){b.off("mousedown",this._onExit);s_oStage.removeChild(b);s_oGame.onPauseExit()};this._init(a);return this}
function CModeMenu(){var a,d,b,c,e,f,g,h,l,k,n,p,t,r,m,w,u;this._init=function(){e=!1;f=NORMAL_MODE;m=createBitmap(s_oSpriteLibrary.getSprite("bg_mode"));s_oStage.addChild(m);g=new createjs.Text(TEXT_DIFFICULTY," 100px blackplotan","#ff8814");g.x=CANVAS_WIDTH/2;g.y=230;g.textAlign="center";g.textBaseline="alphabetic";g.lineWidth=1E3;s_oStage.addChild(g);var v=CANVAS_WIDTH/2,q=s_oSpriteLibrary.getSprite("layout_3x3");h=new CToggle(v-300,400,q,!1);h.addEventListenerWithParams(ON_MOUSE_UP,this._onModeToggle,
this,EASY_MODE);q=s_oSpriteLibrary.getSprite("layout_4x4");l=new CToggle(v,400,q,!0);l.addEventListenerWithParams(ON_MOUSE_UP,this._onModeToggle,this,NORMAL_MODE);q=s_oSpriteLibrary.getSprite("layout_5x5");k=new CToggle(v+300,400,q,!1);k.addEventListenerWithParams(ON_MOUSE_UP,this._onModeToggle,this,HARD_MODE);n=new createjs.Text(TEXT_IMAGE," 100px blackplotan","#ff8814");n.x=CANVAS_WIDTH/2;n.y=690;n.textAlign="center";n.textBaseline="alphabetic";n.lineWidth=1E3;s_oStage.addChild(n);v=CANVAS_WIDTH/
2;q=s_oSpriteLibrary.getSprite("image_1");p=new CGfxButton(v-470,841,q);p.addEventListenerWithParams(ON_MOUSE_UP,this._onButImageRelease,this,"image_1");p.setScale(.24);q=s_oSpriteLibrary.getSprite("image_2");t=new CGfxButton(v-3,841,q);t.addEventListenerWithParams(ON_MOUSE_UP,this._onButImageRelease,this,"image_2");t.setScale(.24);q=s_oSpriteLibrary.getSprite("image_3");r=new CGfxButton(v+465,841,q);r.addEventListenerWithParams(ON_MOUSE_UP,this._onButImageRelease,this,"image_3");r.setScale(.24);
q=s_oSpriteLibrary.getSprite("but_exit");b=CANVAS_WIDTH-q.height/2-10;c=q.height/2+10;w=new CGfxButton(b,c,q,s_oStage);w.addEventListener(ON_MOUSE_UP,this._onExit,this);a=CANVAS_WIDTH-q.width/2-120;d=q.height/2+10;if(!1===DISABLE_SOUND_MOBILE||!1===s_bMobile)q=s_oSpriteLibrary.getSprite("audio_icon"),u=new CToggle(a,d,q,s_bAudioActive),u.addEventListener(ON_MOUSE_UP,this._onAudioToggle,this);this.refreshButtonPos(s_iOffsetX,s_iOffsetY)};this.unload=function(){h.unload();l.unload();k.unload();p.unload();
t.unload();r.unload();s_oModeMenu=null;s_oStage.removeAllChildren()};this.refreshButtonPos=function(e,f){w.setPosition(b-e,f+c);!1!==DISABLE_SOUND_MOBILE&&!1!==s_bMobile||u.setPosition(a-e,f+d)};this._onNumModeToggle=function(a){a===NUM_ACTIVE?(e=!0,(void 0).setActive(!1),(void 0).setActive(!0)):(e=!1,(void 0).setActive(!0),(void 0).setActive(!1))};this._onModeToggle=function(a){switch(a){case 0:h.setActive(!0);l.setActive(!1);k.setActive(!1);f=EASY_MODE;break;case 1:h.setActive(!1);l.setActive(!0);
k.setActive(!1);f=NORMAL_MODE;break;case 2:h.setActive(!1),l.setActive(!1),k.setActive(!0),f=HARD_MODE}};this._onButImageRelease=function(a){this.unload();s_oMain.gotoGame(f,a,e)};s_oModeMenu=this;this._init()}var s_oModeMenu=null;
function CMenu(){var a,d,b,c,e,f;this._init=function(){b=createBitmap(s_oSpriteLibrary.getSprite("bg_menu"));s_oStage.addChild(b);var g=s_oSpriteLibrary.getSprite("but_play");c=new CGfxButton(CANVAS_WIDTH/2,CANVAS_HEIGHT-225,g);c.addEventListener(ON_MOUSE_UP,this._onButPlayRelease,this);if(!1===DISABLE_SOUND_MOBILE||!1===s_bMobile)g=s_oSpriteLibrary.getSprite("audio_icon"),a=CANVAS_WIDTH-g.height/2-10,d=g.height/2+10,f=new CToggle(a,d,g,s_bAudioActive),f.addEventListener(ON_MOUSE_UP,this._onAudioToggle,
this);e=new createjs.Shape;e.graphics.beginFill("black").drawRect(0,0,CANVAS_WIDTH,CANVAS_HEIGHT);s_oStage.addChild(e);createjs.Tween.get(e).to({alpha:0},1E3).call(function(){e.visible=!1});this.refreshButtonPos(s_iOffsetX,s_iOffsetY)};this.unload=function(){c.unload();c=null;e.visible=!1;if(!1===DISABLE_SOUND_MOBILE||!1===s_bMobile)f.unload(),f=null;s_oStage.removeChild(b);s_oMenu=b=null};this.refreshButtonPos=function(b,c){f.setPosition(a-b,c+d)};this._onAudioToggle=function(){createjs.Sound.setMute(s_bAudioActive);
s_bAudioActive=!s_bAudioActive};this._onButPlayRelease=function(){this.unload();!1!==DISABLE_SOUND_MOBILE&&!1!==s_bMobile||createjs.Sound.play("click");s_oMain.gotoModeMenu()};s_oMenu=this;this._init()}var s_oMenu=null;