// pages/product-con/index.js
var app = getApp();
const wxh = require('../../../utils/wxh.js');
const wxParse = require('../../../wxParse/wxParse.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    id:0,
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '商品详情'
    },
    itemNew: [],
    indicatorDots: false,
    circular: true,
    autoplay: true,
    interval: 3000,
    duration: 500,
    attribute: {
      'cartAttr': false
    },
    productSelect: [],
    productAttr: [],
    productValue: [],
    isOpen: false,
    attr: '请选择',
    attrValue: '',
    AllIndex:2,
    replyChance:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    if (options.hasOwnProperty('id')) {
      this.setData({ id: options.id });
      app.globalData.openPages = '/pages/activity/goods_combination_details/index?id=' + this.data.id;
    } else {
      wx.showToast({
        title: '参数错误',
        icon: 'none',
        duration: 1000,
        mask: true,
      });
      setTimeout(function () { wx.navigateBack({ delta: 1 }) }, 1200)
    };
  },
  goProduct:function(){
    return app.Tips('/pages/goods_details/index?id=' + this.data.storeInfo.product_id);
  },
  combinationDetail:function(){
    var that = this;
    var data = {id :that.data.id}; 
    app.basePost(app.U({ c: 'pink_api', a:'combination_detail'}),data,function(res){
      that.setData({
        ["parameter.title"]: res.data.storeInfo.title.substring(0,16),
        imgUrls: res.data.storeInfo.images,
        userInfo: res.data.user,
        storeInfo: res.data.storeInfo,
        pink: res.data.pink,
        pindAll: res.data.pindAll,
        reply: [res.data.reply],
        replyCount: res.data.replyCount,
        itemNew: res.data.pink_ok_list,
        pink_ok_sum: res.data.pink_ok_sum,
        replyChance: res.data.replyChance
      });
      that.setTime();
      wxParse.wxParse('description', 'html', that.data.storeInfo.description, that, 0);
      app.globalData.openPages = '/pages/activity/goods_combination_details/index?id=' + that.data.id + '&scene=' + that.data.userInfo.uid;
      that.setProductSelect();
    },function(res){ console.log(res) });
  },
  onMyEvent: function (e) {
    this.setData({ 'attribute.cartAttr': e.detail.window, isOpen: false })
  },
  setTime: function () {//到期时间戳
    var that = this;
    var endTimeList = that.data.pink;
    var countDownArr = [];
    var timeer=setInterval(function(){
      var newTime = new Date().getTime() / 1000;
      for (var i in endTimeList) {
        var endTime = endTimeList[i].stop_time;
        var obj = [];
        if (endTime - newTime > 0) {
          var time = endTime - newTime;
          var day = parseInt(time / (60 * 60 * 24));
          var hou = parseInt(time % (60 * 60 * 24) / 3600);
          var min = parseInt(time % (60 * 60 * 24) % 3600 / 60);
          var sec = parseInt(time % (60 * 60 * 24) % 3600 % 60);
          hou = parseInt(hou) + parseInt(day * 24);
          obj = {
            day: that.timeFormat(day),
            hou: that.timeFormat(hou),
            min: that.timeFormat(min),
            sec: that.timeFormat(sec)
          }
        } else {
          obj = {
            day: '00',
            hou: '00',
            min: '00',
            sec: '00'
          }
        }
        endTimeList[i].time = obj;
      }
      that.setData({
        pink: endTimeList
      })
    },1000);
    that.setData({
      timeer: timeer
    })
  },
  timeFormat(param) {//小于10的格式化函数
    return param < 10 ? '0' + param : param;
  },
  /**
   * 购物车数量加和数量减
   * 
  */
  ChangeCartNum: function (e) {
    //是否 加|减
    var changeValue = e.detail;
    //获取当前变动属性
    var productSelect = this.data.productValue[this.data.attrValue];
    //如果没有属性,赋值给商品默认库存
    if (productSelect === undefined && !this.data.productAttr.length) productSelect = this.data.productSelect;
    //不存在不加数量
    if (productSelect === undefined) return;
    //提取库存
    var stock = productSelect.stock || 0;
    //设置默认数据
    if (productSelect.cart_num == undefined) productSelect.cart_num = 1;
    //数量+
    if (changeValue) {
      this.setData({
        ['productSelect.cart_num']: productSelect.cart_num,
        cart_num: productSelect.cart_num
      });
    } else {
      this.setData({
        ['productSelect.cart_num']: productSelect.cart_num,
        cart_num: productSelect.cart_num
      });
    }
  },
  /**
   * 属性变动赋值
   * 
  */
  ChangeAttr: function (e) {
    var values = e.detail;
    var productSelect = this.data.productValue[values];
    var storeInfo = this.data.storeInfo;
    if (productSelect) {
      this.setData({
        ["productSelect.image"]: productSelect.image,
        ["productSelect.price"]: productSelect.price,
        ["productSelect.stock"]: productSelect.stock,
        ['productSelect.unique']: productSelect.unique,
        ['productSelect.cart_num']: 1,
        ['productSelect.is_on']: true,
        attrValue: values,
        attr: '已选择'
      });
    } else {
      this.setData({
        ["productSelect.image"]: storeInfo.image,
        ["productSelect.price"]: storeInfo.price,
        ["productSelect.stock"]: 0,
        ['productSelect.unique']: '',
        ['productSelect.cart_num']: 0,
        ['productSelect.is_on']: true,
        attrValue: '',
        attr: '请选择'
      });
    }
  },
  setProductSelect: function () {
    var that = this;
    if (that.data.productSelect.length == 0) {
      that.setData({
        ['productSelect.image']: that.data.storeInfo.image,
        ['productSelect.store_name']: that.data.storeInfo.title,
        ['productSelect.price']: that.data.storeInfo.price,
        ['productSelect.stock']: that.data.storeInfo.stock,
        ['productSelect.unique']: '',
        ['productSelect.cart_num']: 1,
        ['productSelect.is_on']:true
      })
    }
  },
  /*
  * 下订单
  */
  goCat: function () {
    var that = this;
    console.log(that.data.productValue);
    var productSelect = this.data.productValue[this.data.attrValue];
    //打开属性
    if (this.data.isOpen)
      this.setData({ 'attribute.cartAttr': true })
    else
      this.setData({ 'attribute.cartAttr': !this.data.attribute.cartAttr });
    //只有关闭属性弹窗时进行加入购物车
    if (this.data.attribute.cartAttr === true && this.data.isOpen == false) return this.setData({ isOpen: true });
    //如果有属性,没有选择,提示用户选择
    console.log(this.data.productAttr.length);
    if (this.data.productAttr.length && productSelect === undefined && this.data.isOpen == true) return app.Tips({ title: '请选择属性' });
    app.baseGet(app.U({
      c: 'auth_api',
      a: 'now_buy',
      q: {
        productId: that.data.storeInfo.product_id,
        secKillId: 0,
        bargainId: 0,
        combinationId: that.data.id,
        cartNum: that.data.cart_num,
        uniqueId: productSelect !== undefined ? productSelect.unique : ''
      }
    }), function (res) {
      that.setData({ isOpen: false });
      wx.navigateTo({ url: '/pages/order_confirm/index?cartId=' + res.data.cartId });
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },
  onLoadFun:function(){
    this.combinationDetail();
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    if (this.data.isClose) this.combinationDetail();
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({ isClose: 1 });
    this.data.timeer && clearInterval(this.data.timeer);
  },

  showAll:function(){
    if (this.data.AllIndex > this.data.pink.length) 
      this.data.AllIndex = this.data.pink.length;
    else 
      this.data.AllIndex+=2;
    this.setData({ AllIndex: this.data.AllIndex });
  },
  hideAll:function(){
    this.setData({ AllIndex: 2 });
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    var that = this;
    return {
      title: that.data.storeInfo.title,
      path: app.globalData.openPages,
      imageUrl: that.data.storeInfo.image,
      success: function () {
        wx.showToast({
          title: '分享成功',
          icon: 'success',
          duration: 2000
        })
      }
    }
  }
})