'use strict';

var _createClass = (function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ('value' in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; })();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError('Cannot call a class as a function'); } }

var aniLottery = anime({
    targets: '.lottery-wrap'
});

var Turntable = (function () {
    function Turntable(opts) {
        _classCallCheck(this, Turntable);

        this.opts = $.extend(true, {
            target: '.lottery-wrap', // 旋转对象
            easing: 'easeInOutSine', // anime.js 动画曲线
            isplay: false, // 动画是否在播放
            duration: 8000, // 动画时长
            rotateNum: 5, // 旋转圈数
            total: 8, // 奖励个数
            offset: 0 }, // 旋转偏移值
        opts);

        this.opts.angle = 360 / this.opts.total; // 旋转角度
    }

    _createClass(Turntable, [{
        key: 'start',
        value: function start(index, cb) {
            this.opts.isplay = true;

            var self = this,
                opt = this.opts,
                angle = opt.angle,
                off = (opt.total - index) * angle - angle / 2 - opt.offset;

            aniLottery = anime({
                targets: this.opts.target,
                easing: this.opts.easing,
                autoplay: false,
                duration: this.opts.duration,
                rotate: opt.rotateNum * 360 + off,
                complete: function complete() {
                    $(self.opts.target).css({
                        '-webkit-transform': 'rotate(' + off + 'deg)',
                        'transform': 'rotate(' + off + 'deg)'
                    });
                    self.stop();
                    cb && cb(index);
                }
            });
            aniLottery.restart();
        }
    }, {
        key: 'stop',
        value: function stop() {
            this.opts.isplay = false;
            aniLottery.pause();
        }
    }], [{
        key: 'create',
        value: function create(opts) {
            return new Turntable(opts);
        }
    }]);

    return Turntable;
})();