var Coordinator = new Class({
    initialize: function(activities,callback) { //an array of activity names
        this.activities = new Hash();
        activities.each(function(activity) {
            this.activities.set(activity,false);
        }.bind(this));
        this.callback = callback;
    },
    done: function(activity,parameters) {
        this.activities.set(activity,parameters);
        if (this.activities.every(function(activity) {
            return activity;
            })) this.callback(this.activities);
    }
});

