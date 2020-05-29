package com.example.receipttracker;

import androidx.appcompat.app.AppCompatActivity;

import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.TreeMap;

public class LoginPage extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login_page);

        //Initialize the components
        final EditText etEmail = (EditText)findViewById(R.id._etEmail);
        final EditText epPass = (EditText)findViewById(R.id._etPassword);
        Button btnLogin = (Button)findViewById(R.id._btnLogin);
        final TextView status = (TextView)findViewById(R.id._tvStatus);
        //Set starting message.
        status.setText("Enter your credentials to log in.");


        btnLogin.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                status.setText(etEmail.getText() + " " + epPass.getText());
                AttemptLogin attemptLogin= new AttemptLogin();
                attemptLogin.execute(etEmail.getText().toString(),epPass.getText().toString());
            }
        });
    }

    private class AttemptLogin extends AsyncTask<String, String, JSONObject>
    {
        //used this as a guide https://www.journaldev.com/12607/android-login-registration-php-mysql#android-login-registration-app
        //https://camposha.info/android-php-mysql-save-http-post-httpurlconnection/

        @Override
        protected void onPreExecute() {
            super.onPreExecute();
        }

        @Override
        protected JSONObject doInBackground(String... strings) {
            try {
                URL url = new URL("http://www.mokarrom.com/ReceiptWebservice/androidLogin.php");
                HttpURLConnection urlConnection = (HttpURLConnection)url.openConnection();
                urlConnection.setRequestMethod("POST");
                urlConnection.setRequestProperty("submit", "Login");
                urlConnection.setRequestProperty("email", strings[0]);
                urlConnection.setRequestProperty("password", strings[1]);

                try {
                    BufferedReader reader = new BufferedReader(new InputStreamReader(urlConnection.getInputStream()));
                    StringBuilder stringBuilder = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        stringBuilder.append(line);
                    }
                    //Parse the data from the API using JSON
                    JSONObject data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                    JSONArray results =  data.getJSONArray("results");
                    //Log.d("Objects in data", data.names().toString(2));

                    for(Integer i = 0; i < results.length(); ++i)
                    {
                        Log.d("Reading JSON Results", results.getJSONObject(i).get("name").toString());
                    }

                }
                finally {
                    urlConnection.disconnect();

                }
            }
            catch (Exception e) {
                Log.e("Something went wrong", e.toString());
            }

            return null;
        }
    }

}
