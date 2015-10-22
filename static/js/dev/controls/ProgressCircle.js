var STEP = Math.PI * 0.5,
    FULL_CIRCLE = Math.PI * 2,
    CIRCLE_TOP = Math.PI * 1.5;

var ProgressCircle = function($el, max, diameter, color, lineThickness) {
    var canvas = document.createElement('canvas'),
        context = canvas.getContext('2d');

    this.value = 0;
    this.max = max;
    this.center = diameter / 2;

    this.diameter = (diameter - lineThickness) / 2;
    canvas.width = diameter;
    canvas.height = diameter;
    this.canvas = canvas;
    context.strokeStyle = color;
    context.lineWidth = lineThickness;
    this.context = context;

    $el.append(canvas);
    this._draw();

};

RB.ProgressCircle.prototype.progress = function(value) {
    this.value = value;
    this._draw();
};

RB.ProgressCircle.prototype._draw = function() {
    var canvas = this.canvas,
        context = this.context,
        arc = this.value / this.max;

    arc = arc > 1 ? 1 : arc;
    arc = FULL_CIRCLE * arc;

    context.beginPath();
    context.arc(this.center, this.center, this.diameter, 0, arc);
    context.stroke();

};

export default ProgressCircle;