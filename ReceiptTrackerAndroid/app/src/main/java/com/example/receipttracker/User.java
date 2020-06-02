package com.example.receipttracker;

import android.os.Parcel;
import android.os.Parcelable;

import org.json.JSONException;
import org.json.JSONObject;

public class User implements Parcelable {
    private int userID; //UserID in the database
    private String token;   //REST API authorization token

    //User Class constructor
    //Assign the userID and the token to the user.
    public User(JSONObject jsonObject) throws JSONException {
        try {
            userID = Integer.parseInt(jsonObject.getString("userID").toString());
            token = jsonObject.getString("token");
        }
        catch (JSONException e)
        {
            e.printStackTrace();
        }
    }

    //Constructor used by the parcel.
    protected User(Parcel in) {
        userID = in.readInt();
        token = in.readString();
    }

    public static final Creator<User> CREATOR = new Creator<User>() {
        @Override
        public User createFromParcel(Parcel in) {
            return new User(in);
        }

        @Override
        public User[] newArray(int size) {
            return new User[size];
        }
    };

    public int getUserID()
    {
        return userID;
    }

    public String getToken()
    {
        return token;
    }


    @Override
    public int describeContents() {
        return 0;
    }

    @Override
    public void writeToParcel(Parcel dest, int flags) {
        dest.writeInt(userID);
        dest.writeString(token);
    }
}
