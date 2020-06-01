package com.example.receipttracker;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.ArrayMap;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.lang.ref.WeakReference;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;

import javax.net.ssl.HttpsURLConnection;

public class SignUpPage extends AppCompatActivity {

    TextView status;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_sign_up_page);

        //Initialize the components of the page
        final EditText etFName = (EditText)findViewById(R.id._etFName);
        final EditText etLName = (EditText)findViewById(R.id._etLName);
        final EditText etEmail = (EditText)findViewById(R.id._etEmail);
        final EditText etPass1 = (EditText)findViewById(R.id._etPassword1);
        final EditText etPass2 = (EditText)findViewById(R.id._etPassword2);
        Button btnSignUp = (Button)findViewById(R.id._btnRegister);
        status = (TextView)findViewById(R.id._tvStatus);
        status.setText("Please enter the above information to create an account.");

        btnSignUp.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                //Ensure that all fields are filled in
                if(etFName.getText().length() < 1 ||
                    etLName.getText().length() < 1 ||
                    etEmail.getText().length() < 1 ||
                    etPass1.getText().length() < 1 ||
                    etPass2.getText().length() < 1)
                {
                    status.setText("You are missing important field(s).");
                    return;
                }

                //All fields are filled in and therefore the account can be created.
                ArrayMap<String,String> userCredentialsArrMap = new ArrayMap<String,String>();
                userCredentialsArrMap.put("submit", "Register");
                userCredentialsArrMap.put("firstName", etFName.getText().toString());
                userCredentialsArrMap.put("lastName", etLName.getText().toString());
                userCredentialsArrMap.put("email", etEmail.getText().toString());
                userCredentialsArrMap.put("password1", etPass1.getText().toString());
                userCredentialsArrMap.put("password2", etPass2.getText().toString());

                SignUp signUp = new SignUp(SignUpPage.this);
                signUp.execute(userCredentialsArrMap);
            }
        });

    }

    private static class SignUp extends AsyncTask<ArrayMap<String,String>, String, JSONObject>
    {
        private JSONObject returnedObject;
        //Weak reference to this page
        private WeakReference<SignUpPage> signUpPageWeakReference;

        public SignUp(SignUpPage signUpPage)
        {
            signUpPageWeakReference = new WeakReference<SignUpPage>(signUpPage);
        }

        //Tell the user that the account is being registered
        @Override
        protected void onPreExecute() {
            super.onPreExecute();
            SignUpPage signUpPage = signUpPageWeakReference.get();

            if(signUpPage == null || signUpPage.isFinishing())
                return;

            signUpPage.status.setText("Your account is being created.");
        }

        //used this as a guide https://www.journaldev.com/12607/android-login-registration-php-mysql#android-login-registration-app
        //https://camposha.info/android-php-mysql-save-http-post-httpurlconnection/
        //https://stackoverflow.com/questions/9767952/how-to-add-parameters-to-httpurlconnection-using-post-using-namevaluepair
        //https://stackoverflow.com/questions/4205980/java-sending-http-parameters-via-post-method-easily
        //https://stackoverflow.com/questions/40574892/how-to-send-post-request-with-x-www-form-urlencoded-body
        //https://prodevsblog.com/view/android-httpurlconnection-post-and-get-request-tutorial/

        //Send the data to the server to register this account
        @Override
        protected JSONObject doInBackground(ArrayMap<String, String>... arrayMaps) {
            try {
                //create the parameters string to log in
                StringBuilder urlParameters = new StringBuilder();
                for(Integer i = 0; i < arrayMaps[0].size(); ++i)
                {
                    urlParameters.append(URLEncoder.encode(arrayMaps[0].keyAt(i), "UTF-8"));
                    urlParameters.append("=");
                    urlParameters.append(URLEncoder.encode(arrayMaps[0].valueAt(i), "UTF-8"));

                    if(i < arrayMaps[0].size() - 1)
                    {
                        urlParameters.append("&");
                    }
                }
                byte[] postData = urlParameters.toString().getBytes(StandardCharsets.UTF_8);

                //URL and HTTP POST connection to the server.
                URL url = new URL("http://www.mokarrom.com/ReceiptWebservice/androidRegistration.php");
                HttpURLConnection urlConnection = (HttpURLConnection)url.openConnection();
                urlConnection.setRequestMethod("POST");
                urlConnection.setReadTimeout(20000);
                urlConnection.setConnectTimeout(20000);
                urlConnection.setDoInput(true);
                urlConnection.setDoOutput(true);
                urlConnection.setRequestProperty( "Content-Type", "application/x-www-form-urlencoded");
                urlConnection.setRequestProperty( "charset", "utf-8");
                urlConnection.setRequestProperty( "Content-Length", Integer.toString( postData.length ));
                urlConnection.setUseCaches( false );

                //Try to send the data and read the response
                try {
                    //Send the data with an output stream
                    OutputStream os = urlConnection.getOutputStream();
                    BufferedWriter bw = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                    bw.write(urlParameters.toString());
                    //flush the writer and close it and the output stream
                    bw.flush();
                    bw.close();
                    os.close();

                    if(urlConnection.getResponseCode() == HttpsURLConnection.HTTP_OK) {
                        BufferedReader reader = new BufferedReader(new InputStreamReader(urlConnection.getInputStream()));
                        StringBuilder stringBuilder = new StringBuilder();
                        String line;
                        while ((line = reader.readLine()) != null) {
                            stringBuilder.append(line);
                        }
                        //Parse the data from the API using JSON
                        JSONObject data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                        //String myToken = data.getString("token");
                        Log.d("Token found = ", "myToken");

                        //Assign the found jason object the class JSONObject
                        returnedObject = data;
                    }
                }
                finally {
                    urlConnection.disconnect();
                    return returnedObject;
                }
            }
            catch (Exception e) {
                Log.e("Something went wrong", e.toString());
            }

            return null;
        }

        //Tell the user the account has been registered and send them back to the login page.
        @Override
        protected void onPostExecute(JSONObject jsonObject) {
            super.onPostExecute(jsonObject);

            //Get a strong reference to the SignUp  page and make sure that it
            //is not null or finishing
            SignUpPage signUpPage = signUpPageWeakReference.get();
            if(signUpPage == null || signUpPage.isFinishing())
                return;

            //try to read info from the JSONObject
            try
            {
                //signUpPage.status.setText(jsonObject.getString("status"));
                Toast toast = Toast.makeText(signUpPage.getApplicationContext(), jsonObject.getString("status"), Toast.LENGTH_LONG);
                toast.show();
                if(jsonObject.getBoolean("otherIssues"))
                {
                    return;
                }

                //idk if this are necessary
//                if(!jsonObject.getBoolean("userExists"))
//                {
//                    signUpPage.status.setText(jsonObject.getString("status"));
//                }

                //need a delay here somehow to show the message before leaving this page
                if(jsonObject.getBoolean("userCreatedSuccess"))
                {
                    signUpPage.finish();
                }
            }
            catch (JSONException e)// | InterruptedException e)
            {
                e.printStackTrace();
            }
            //signUpPage.status.setText("Your account has been created. Please check your email for the verification link.");
        }
    }
}
