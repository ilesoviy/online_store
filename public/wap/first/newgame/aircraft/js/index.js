// 分辨率
var diviceHeight = document.documentElement.clientHeight
var diviceWidth = document.documentElement.clientWidth;


var canvas1 = document.getElementById("canvas1");
canvas1.width = diviceWidth;
canvas1.height = diviceHeight;

var context = canvas1.getContext('2d');
var canvasbig = document.getElementsByClassName("canvasbig")[0];
//背景图片
var bg = new Image();
bg.src = "img/bg.jpg";
//全民飞机大战标题
var starthead = new Image();
starthead.src = "img/starthead.png";

//加载时候的狗子和文字
var load = new Image();
var loadnum = 1,
    loadtime = 0,
    loadrect = 1;
var loadtextblur = true,
    loadtextnum = -1,
    pointnum = 1;

//我方战斗机
var myplane = new Image();
myplane.src = "img/myplane1.png";
var myplaneX = canvas1.width / 2,
    myplaneY = diviceHeight - 60;
//战斗机子弹
var bullet = new Image();
bullet.src = "img/bullet.png";
var bullettime = 0,
    bulletnum = 0,
    bulletarr = [];
//敌机
var enemytime = 0,
    enemyarr = [];
var enemy1 = new Image();
enemy1.src = `img/enemy1.png`;
var enemy2 = new Image();
enemy2.src = `img/enemy2.png`;
var enemy3 = new Image();
enemy3.src = `img/enemy3.png`;

var enemyall = [enemy1, enemy2, enemy3];
//战斗机爆炸
var myplane1boom = new Image();
var myboomnum = 1,
    myboomtime = 0;
//敌机爆炸
var enemychangearr = [];
//boss警告
var warning1 = new Image();
warning1.src = "img/warning1.png"
var warning2 = new Image();
warning2.src = "img/warning2.png";
var warningtime = 0,
    warningchange = 0;
//boss出场背景
var bossbg = new Image();
bossbg.src = "img/bg2.jpg";
var boss = new Image();
boss.src = "img/planeboss.png";
//boss改变飞机速度
var bossattacktime = 0;
var bossattacknum = 1;

document.body.addEventListener('touchmove', function(e) {
    e.preventDefault();
}, {
    passive: false
});

// 对象
var obj = {
    // 游戏开始
    gamestart: 1,
    // 游戏加载
    gameload: 0,

    gamerun: 0,
    // 游戏结束
    gameover: 0,
    // 死亡
    dead: 0,
    score: 0,
    life: 2,
    bgy1: -diviceHeight,
    bgy2: 0,
    warnon: 0,
    bosstime: 0,
    bossattack: 0,
    // 初始化背景
    bgon: function() {
        context.drawImage(bg, 0, this.bgy1, 520, diviceHeight);
        context.drawImage(bg, 0, this.bgy2, 520, diviceHeight);
    },
    // 背景无缝切换
    bgchange: function() {
        this.bgy1++;
        this.bgy2++;
        if (this.bgy1 == 0) {
            this.bgy1 = -diviceHeight;
            this.bgy2 = 0;
        }
    },
    // 获取得到的分数
    scoring: function() {
        var gradient = context.createLinearGradient(0, 0, 120, 60);
        gradient.addColorStop(0, '#ff9569');
        gradient.addColorStop(1, '#e92758');
        context.font = '22px  sans-serif';
        context.fillStyle = gradient;
        context.fillText("SCORE:" + this.score, 10, 50);
    },
    lifeing: function() {
        context.font = '22px  sans-serif';
        context.fillStyle = "#D28140";
        context.fillText("LIFE:" + this.life, diviceWidth - 70, 50);
        if (obj.dead == 1 && myboomnum == 9 && obj.life > 0) {
            obj.dead = 0;
            bullettime = 0;
            bulletnum = 0;
            bulletarr = [];
            enemytime = 0;
            enemyarr = [];
            myboomnum = 1;
            myboomtime = 0;
            enemychangearr = [];
            myplane1boom.src = `img/myplane1boom${myboomnum}.png`;
        } else if (obj.dead == 1 && obj.life == 0) {
            obj.gameover = 1;
        }
    },
    gameovering: function() {
        // let ifYouDieMb = document.querySelector('#ifYouDie')
        if (obj.gameover == 1) {
            obj.gamestart = 1;
            obj.gameover = 0;
            obj.dead = 0;
            obj.gamerun = 0;
            // ifYouDieMb.style.display = 'flex';
            $.ajax({
                url: '/wap/game/aircraftUpdate',
                type: 'post',
                dataType: 'json',
                data: {
                    integral: this.score
                },
                success: function(res) {
                    if (res.code == 200) {
                        alert("恭喜获得" + res.data.jifen + "积分！");
                        // load_next_stage();
                    } else {
                        alert(res.msg);
                    }
                }
            });
        }
    },
    starting: function() {
        canvasdiv.className = "canvasdiv";
        obj.life = 2;
        obj.score = 0;
        loadnum = 1;
        loadtime = 0;
        loadrect = 1;
        loadtextblur = true;
        loadtextnum = -1;
        pointnum = 1;
        myplaneX = canvas1.width / 2;
        myplaneY = diviceHeight - 60;
        bullettime = 0;
        bulletnum = 0;
        bulletarr = [];
        enemytime = 0;
        enemyarr = [];
        myboomnum = 1;
        myboomtime = 0;
        enemychangearr = [];
        warningtime = 0;
        warningchange = 0;
        bossattacktime = 0;
        bossattacknum = 1;
        obj.bossbgy1 = -2420,
            obj.bossbgy2 = -1640,
            obj.bossbgy3 = -860,
            obj.bg2boss = -262,
            obj.bosstimeblur = true,
            obj.bossattack = 0;
        context.drawImage(starthead, (diviceWidth - starthead.width) / 2, 200);
    },

    // 加载动画
    loading: function() {
        loadtime++;
        loadrect++;
        if (loadtime == 5) {
            loadtime = 0;
            loadnum++;
            if (loadnum == 10) {
                loadnum = 1;
            }
            load.src = `img/load${loadnum}.png`;
        }
        context.beginPath();
        context.fillStyle = 'white';
        context.fillRect(0, 0, diviceWidth, diviceHeight);
        var gradient = context.createLinearGradient(20, 0, 397, 30);
        gradient.addColorStop(0, '#29bdd9');
        gradient.addColorStop(1, '#276ace');
        context.fillStyle = gradient;
        context.fillRect(20, 500, loadrect, 30);
        context.closePath();
        context.drawImage(load, loadrect + 20, 480, 102, 72);

    },
    // 加载动画
    loadtext: function() {
        if (loadtextblur == false) {
            loadtextnum--;
        } else if (loadtextblur == true) {
            loadtextnum += 2;
        }
        context.beginPath();
        context.font = '40px  sans-serif';
        context.fillStyle = 'black';
        context.fillText("加载中 ", 50, 450);
        context.beginPath();
        context.font = '80px  sans-serif';
        if (pointnum == 1) {
            context.fillText(". ", 200, 450 - loadtextnum);
            context.fillText(". ", 240, 450);
            context.fillText(". ", 280, 450);
            if (loadtextnum < 0) {
                pointnum = 2;
                loadtextnum = 0;
                loadtextblur = true;
            } else if (loadtextnum > 39) {
                loadtextblur = false;
            }
        } else if (pointnum == 2) {
            context.fillText(". ", 200, 450);
            context.fillText(". ", 240, 450 - loadtextnum);
            context.fillText(". ", 280, 450);
            if (loadtextnum < 0) {
                pointnum = 3;
                loadtextnum = 0;
                loadtextblur = true;
            } else if (loadtextnum > 39) {
                loadtextblur = false;
            }
        } else if (pointnum == 3) {
            context.fillText(". ", 200, 450);
            context.fillText(". ", 240, 450);
            context.fillText(". ", 280, 450 - loadtextnum);
            if (loadtextnum < 0) {
                pointnum = 1;
                loadtextnum = 0;
                loadtextblur = true;
            } else if (loadtextnum > 39) {
                loadtextblur = false;
            }
        }
        context.closePath();
    },
    // 我的飞机
    myplane: function(e) {
        myplaneX = e.targetTouches[0].clientX;
        myplaneY = e.targetTouches[0].clientY;
        context.drawImage(myplane, myplaneX - myplane.width / 2, myplaneY - myplane.height / 2);
    },
    bulleton: function() {
        bullettime++;
        var bulletX = myplaneX - bullet.width / 2;
        var bulletY = myplaneY - myplane.height / 2 - bullet.height;
        var num;
        if (obj.bossattack == 1) {
            num = 10
        } else {
            num = 20
        }
        if (bullettime >= num) {
            var changearr = [bulletX, bulletY, 0];
            bulletarr.push(changearr);
            bullettime = 0;
        }
    },
    bulletchange: function() {
        var result = [];
        for (var i = 0; i < bulletarr.length; i++) {
            if (bulletarr[i][1] - bulletarr[i][2] >= 0) {
                context.drawImage(bullet, bulletarr[i][0], bulletarr[i][1] - bulletarr[i][2]);
                bulletarr[i][2] += 4;
                result.push(bulletarr[i]);
            }
        }
        bulletarr = result;
    },
    enemy: function() {
        enemytime++;
        var enemynum = parseInt(Math.random() * 4);
        if (obj.bossattack == 1) {
            num = 10
        } else {
            num = 40
        }
        if (enemytime >= num) {
            if (enemynum == 3 && Math.random() < 0.9) {
                return;
            } else {
                var enemylife = 2
                if (enemynum == 3) {
                    enemylife = 5
                }
                var changearr = [Math.random() * diviceWidth - enemyall[enemynum].width / 2, -enemyall[enemynum].height, 0, enemynum, enemylife];
                enemyarr.push(changearr);
                enemytime = 0;
            }
        }
    },
    enemychange: function() {
        var result = [];
        if (obj.bossattack == 1) {
            bossattacktime++;
            if (bossattacktime == 80) {
                bossattacknum += 0.05;
                bossattacktime = 0;
            }
        }
        for (let i = 0; i < enemyarr.length; i++) {
            if (enemyarr[i][1] + enemyarr[i][2] <= canvas1.height) {
                context.drawImage(enemyall[enemyarr[i][3]], enemyarr[i][0], enemyarr[i][1] + enemyarr[i][2]);
                if (enemyall[enemyarr[i][2]] == enemy3) {
                    enemyarr[i][2] += 1.5 * bossattacknum;
                } else {
                    enemyarr[i][2] += 2 * bossattacknum;
                }
                result.push(enemyarr[i]);
            }
        }
        enemyarr = result;
    },
    myplaneboom: function() {
        obj.dead = 1;
        myboomtime++;
        if (myboomtime >= 10) {
            myplane1boom.src = `img/myplane1boom${myboomnum}.png`;
            myboomnum++;
            myboomtime = 0;
        }
        context.drawImage(myplane1boom, myplaneX - myplane.width / 2, myplaneY - myplane.height / 2);
        if (myboomnum == 9) {
            obj.life -= 1;
            bulletarr = [];
            enemyarr = [];
            myplaneX = canvas1.width / 2;
            myplaneY = diviceHeight - 60;
        }
    },
    myplaneisbroke: function() {
        for (let i = 0; i < enemyarr.length; i++) {
            if (enemyarr[i][0] < myplaneX - myplane.width / 2 && enemyarr[i][1] + enemyarr[i][2] < myplaneY - myplane.height / 2 + myplane.height) {
                if (enemyarr[i][0] + enemyall[enemyarr[i][3]].width > myplaneX - myplane.width / 2 && enemyarr[i][1] + enemyarr[i][2] + enemyall[enemyarr[i][3]].height > myplaneY - myplane.height / 2) {
                    obj.myplaneboom();
                }
            } else if (enemyarr[i][0] > myplaneX - myplane.width / 2 && enemyarr[i][1] + enemyarr[i][2] < myplaneY - myplane.height / 2 + myplane.height) {
                if (enemyarr[i][0] < myplaneX - myplane.width / 2 + myplane.width && enemyarr[i][1] + enemyarr[i][2] + enemyall[enemyarr[i][3]].height > myplaneY - myplane.height / 2) {
                    obj.myplaneboom();
                }
            }
        }
    },
    enemyboom: function() {
        var result = [];
        for (let i = 0; i < enemychangearr.length; i++) {
            enemychangearr[i][3]++;
            if (enemychangearr[i][3] >= 10) {
                enemychangearr[i][5].src = `img/enemy${enemychangearr[i][2]}boom${enemychangearr[i][4]}.png`;
                enemychangearr[i][4]++;
                enemychangearr[i][3] = 0;
            }
            context.drawImage(enemychangearr[i][5], enemychangearr[i][0], enemychangearr[i][1]);
            if (enemychangearr[i][4] < 6) {
                result.push(enemychangearr[i]);
            }
        };
        enemychangearr = result;
    },
    enemyisbroke: function() {
        for (let i = 0; i < bulletarr.length; i++) {
            for (let x = 0; x < enemyarr.length; x++) {
                if (bulletarr[i][0] < enemyarr[x][0] && bulletarr[i][1] - bulletarr[i][2] < enemyarr[x][1] + enemyarr[x][2] + enemyall[enemyarr[x][3]].height && bulletarr[i][1] - bulletarr[i][2] > enemyarr[x][1] + enemyarr[x][2]) {
                    if (bulletarr[i][0] + bullet.width > enemyarr[x][0]) {
                        enemyarr[x][4]--;
                        if (enemyarr[x][4] == 0) {
                            var enemyboom = new Image();
                            enemychangearr.push([enemyarr[x][0], enemyarr[x][1] + enemyarr[x][2], enemyarr[x][3] + 1, 0, 1, enemyboom]);
                            if (enemyarr[x][3] == 3) {
                                obj.score += 10;
                            } else {
                                obj.score += 2;
                            }
                            enemyarr.splice(x, 1);
                        }
                        bulletarr.splice(i, 1);
                    }
                } else if (bulletarr[i][0] > enemyarr[x][0] && bulletarr[i][1] - bulletarr[i][2] < enemyarr[x][1] + enemyarr[x][2] + enemyall[enemyarr[x][3]].height && bulletarr[i][1] - bulletarr[i][2] > enemyarr[x][1] + enemyarr[x][2]) {
                    if (bulletarr[i][0] < enemyarr[x][0] + enemyall[enemyarr[x][3]].width) {
                        enemyarr[x][4]--;
                        if (enemyarr[x][4] == 0) {
                            var enemyboom = new Image();
                            enemychangearr.push([enemyarr[x][0], enemyarr[x][1] + enemyarr[x][2], enemyarr[x][3] + 1, 0, 1, enemyboom]);
                            if (enemyarr[x][3] == 3) {
                                obj.score += 10;
                            } else {
                                obj.score += 2;
                            }
                            enemyarr.splice(x, 1);
                        }
                        bulletarr.splice(i, 1);
                    }
                }
            }
        }
    },
    warning: function() {
        warningchange++;
        warningtime++;
        if (warningtime >= 20) {
            context.drawImage(warning1, 150, 200);
            context.drawImage(warning2, 190, 400);
            if (warningtime >= 80) {
                warningtime = 0;
            }
        }
        if (warningchange == 500) {
            obj.warnon = 0;
            obj.bosstime = 1;
        }
    },
    bossbgy1: -2420,
    bossbgy2: -1640,
    bossbgy3: -860,
    bg2boss: -262,
    bosstimeblur: true,
    bossbgon: function() {
        context.drawImage(bossbg, 0, this.bossbgy1)
        context.drawImage(bossbg, 0, this.bossbgy2);
        context.drawImage(bossbg, 0, this.bossbgy3);
        if (obj.bosstime == 1) {
            context.drawImage(boss, 0, this.bg2boss);
        }
    },
    bossbgchange: function() {
        this.bossbgy1 += 2;
        this.bossbgy2 += 2;
        this.bossbgy3 += 2;
        this.bg2boss += 2;
        if (this.bg2boss == 800) {
            this.bosstime = 0;
            this.bossattack = 1;
        };
        if (this.bossbgy3 == 800) {
            this.bossbgy1 = -1540;
            this.bossbgy2 = -760;
            this.bossbgy3 = 20;
        }
    },
    bossY: -262,
    bossX: 0,
    bossfirstime: 0,
    bosssecondtime: 0,
    beatboss: function() {
        context.drawImage(boss, this.bossX, this.bossY);
        if (obj.bossY <= 0) {
            obj.bossfirstime++;
            if (obj.bossfirstime == 4) {
                obj.bossY++;
                obj.bossfirstime = 0;
            }
        } else {
            obj.bosssecondtime++;
            if (obj.bosssecondtime == 2) {
                obj.bossX++;
                obj.bossfirstime = 0;
            }
        }
    },
    gua: function() {
        enemychangearr = [];
        for (let x = 0; x < enemyarr.length; x++) {
            var enemyboom = new Image();
            enemychangearr.push([enemyarr[x][0], enemyarr[x][1] + enemyarr[x][2], enemyarr[x][3] + 1, 0, 1, enemyboom]);
        }
        enemyarr = [];
    }
}

setInterval(function() {
    obj.bgon();
    obj.bgchange();
    if (obj.gamestart == 1) {
        obj.starting();
    }
    if (obj.gameload == 1) {
        if (loadrect >= (diviceWidth - 60)) {
            obj.gameload = 0;
            obj.gamerun = 1;
        }
        obj.loading();
        obj.loadtext();
    }
    if (obj.gamerun == 1) {
        // if (obj.score >= 10 && obj.bosstimeblur == true) {
        //     obj.warnon = 1;
        //     obj.gua();
        //     obj.bosstimeblur = false;
        // }
        // if (obj.bosstime == 1 || obj.bossattack == 1) {
        //     obj.bossbgon();
        //     obj.bossbgchange();
        // }
        if (obj.dead == 0) {
            context.drawImage(myplane, myplaneX - myplane.width / 2, myplaneY - myplane.height / 2);
            if (obj.bosstime == 0 && obj.warnon == 0) {
                obj.enemy();
                obj.enemychange();
            }
            obj.bulleton();
            obj.bulletchange();
            // if (obj.warnon == 1) {
            //     obj.warning();
            // }
        }
        obj.myplaneisbroke();
        obj.enemyisbroke();
        obj.enemyboom();
        obj.lifeing();
        obj.gameovering();
        obj.scoring();
    }

}, 10)



var canvasdiv = document.getElementsByClassName("canvasdiv")[0];
canvasdiv.onclick = function() {
    canvasdiv.className = "canvasdiv none";
    obj.gamestart = 0;
    obj.gameload = 1;
}

canvas1.ontouchmove = function(e) {
    if (obj.gamerun == 1 && obj.dead == 0) {
        obj.myplane(e);
        this.style.cursor = "none";
    } else {
        this.style.cursor = "";
    }
}
document.onkeydown = function(event) {
    if (event.keyCode == 8 && obj.gamerun == 1) {
        obj.gua();
    };
}

// 重来